<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MediaRecordController as TrainerMediaRecordController;
use Illuminate\Contracts\View\View;

/**
 * クライアント閲覧機能（柱2）— ダッシュボードコントローラ
 *
 * ログイン中のクライアント自身のトレーニング記録を、セッションカード・
 * フィードとしてビューに渡す。段階③でトレーニーの体重推移グラフ用の
 * データも組み立てて渡す（設計書 §S-1402 の「体重推移」節、6-15-14）。
 *
 * 設計書 §8-1-1 の方針に従う：
 * - 既存クエリはそのまま（eager load / withCount / training_date 降順は不変）
 * - 記録ごとに入れ子の $sessions 構造を返す（前案の平坦化 $mediaItems は撤回）
 * - 署名付きサムネイル URL 等はコントローラで生成し、Blade からモデルの
 *   temporaryThumbnailUrl() を呼ばない
 * - 有効期限（$thumbnailExpiresAt）の算出はそのまま維持
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $client = auth('client')->user();

        // 自分の記録を日付の新しい順（降順）で取得。
        // 一覧表示に使うリレーション（担当1・担当2）に加え、
        // メディアもカード内に埋め込むため mediaRecords を eager load（N+1回避）。
        // updatedBy はクライアント非表示のため意図的にロードしない。
        $records = $client
            ->trainingRecords()
            ->with(['trainer1', 'trainer2', 'mediaRecords'])
            ->withCount('mediaRecords')
            ->orderByDesc('training_date')
            ->get();

        // 記録ごとに入れ子の $sessions 構造に組み替える（設計書 §8-1-1 変更1）。
        // 署名付きサムネイル URL は temporaryThumbnailUrl() を呼ぶ必要があるため、
        // 有効期限をここで算出しコントローラで一括生成する（Blade で呼ばない）。
        $thumbnailExpiresAt = now()->addMinutes(
            TrainerMediaRecordController::PLAY_URL_EXPIRES_MINUTES
        );

        // displayTitle は渡さない。お客様側の Blade は種別ラベル（写真／動画）を
        // alt / aria-label / data-display-title に載せる（設計書 S-1402 メディアギャラリー
        // 節参照。メディアの表示名・ファイル名はクライアント側に一切露出させない方針）。
        // typeLabel はコントローラで組み立てて渡す（Blade で `@php(...)` を使うと
        // 直前の `@php ... @endphp` ブロックとパースが衝突する事故があったため、
        // Blade 側の @php 使用を避けている）。photo / video の 2 分岐は DB CHECK 制約に対応。
        $sessions = $records->map(function ($rec) use ($thumbnailExpiresAt) {
            return [
                'record' => $rec,
                'media'  => $rec->mediaRecords->map(function ($m) use ($thumbnailExpiresAt) {
                    return [
                        'id'               => $m->id,
                        'type'             => $m->type,
                        'typeLabel'        => $m->type === 'photo' ? '写真' : '動画',
                        'thumbnailUrl'     => $m->temporaryThumbnailUrl($thumbnailExpiresAt),
                        'conversionStatus' => $m->conversion_status,
                    ];
                })->all(),
            ];
        });

        // 段階③：トレーニーごとの体重推移グラフ用データを組み立てる。
        // Blade には完成した配列を渡す（Blade 内での再計算・並べ替えは行わない）。
        $weightCharts = $this->buildWeightCharts($client);

        return view('client.dashboard', compact('sessions', 'weightCharts'));
    }

    /**
     * 体重推移グラフ用のデータをトレーニーごとに組み立てる。
     *
     * 返す配列の形（トレーニーごと。**計測値 0 件のトレーニーも含める**が、その場合は
     * labels / tooltips / datasets が空配列。Blade 側で `empty($chart['labels'])` を
     * 判定して「まだ計測値がありません」の案内に切り替える。詳細は
     * screen-design.md S-1402「体重推移」設計方針の 2026-09 変更参照）:
     * [
     *   [
     *     'id'       => (int) トレーニーID,
     *     'name'     => (string) トレーニー名,
     *     'labels'   => ['M/D', 'M/D', ...],   // 昇順の日付ラベル（軸表示用）。0 件時は []
     *     'tooltips' => ['YYYY/M/D HH:MM', ...] // 対応するツールチップ用の日時。0 件時は []
     *     'datasets' => [                       // 分割された線のセグメントごと。0 件時は []
     *       ['data' => [x1, null, null, ...], ...],
     *       ['data' => [null, null, x3, x4, ...], ...],
     *     ],
     *   ],
     *   ...
     * ]
     *
     * トレーニー 0 頭の会員では空配列を返し、Blade で `@if(!empty($weightCharts))` により
     * ブロックごと非表示になる（この挙動は変更なし）。
     *
     * 分割の判定は「前回の計測日から `chart_gap_split_days` **日以上**空いたら分割」
     * （設計書 6-15-14。閾値は architecture.md §3-3 の設定値、既定 14 日）。
     */
    private function buildWeightCharts($client): array
    {
        $threshold = (int) config('trainee_measurements.chart_gap_split_days');

        // トレーニーは Trainee::trainees() で `id` 昇順（登録順）が既定。
        // measurements は Trainee::measurements() で日時**降順**なので、
        // グラフ用には昇順に並べ直す。
        $trainees = $client->trainees()->with('measurements')->get();

        $charts = [];
        foreach ($trainees as $trainee) {
            // 昇順にソート（日付 → 時刻の順）
            $measurements = $trainee->measurements
                ->sortBy([
                    ['measured_date', 'asc'],
                    ['measured_time', 'asc'],
                ])
                ->values();

            if ($measurements->isEmpty()) {
                // 計測値 0 件のトレーニーもカードを出す（Blade で空判定して
                // 「まだ計測値がありません」を表示。空のグラフは描かない）。
                // 設計書 S-1402「体重推移」設計方針の 2026-09 変更参照。
                $charts[] = [
                    'id' => $trainee->id,
                    'name' => $trainee->name,
                    'labels' => [],
                    'tooltips' => [],
                    'datasets' => [],
                ];
                continue;
            }

            // ラベルとツールチップ用文字列を組み立てる。
            // 軸ラベルは日付のみ（M/D）。同日 2 回計測がある場合も同じ表記が並ぶが、
            // ツールチップに時刻まで含めるため区別できる。
            $labels = [];
            $tooltips = [];
            foreach ($measurements as $m) {
                $date = $m->measured_date;
                $time = substr($m->measured_time, 0, 5); // 'HH:MM'
                $labels[] = $date->format('n/j');
                $tooltips[] = $date->format('Y/n/j') . ' ' . $time;
            }

            // 分割された各セグメントを別データセットとして渡す（すべての値がある位置以外は null）。
            // 「前回の計測日からのカレンダー日数の差」で判定し、
            // 差が閾値以上ならそこで新しいセグメントを開始する。
            $segments = [];
            $currentSegment = [];
            $prevDate = null;
            foreach ($measurements as $index => $m) {
                if ($prevDate !== null) {
                    $daysDiff = $prevDate->diffInDays($m->measured_date);
                    if ($daysDiff >= $threshold) {
                        // 閾値以上空いた → 前のセグメントを閉じ、新しいセグメントを開始
                        $segments[] = $currentSegment;
                        $currentSegment = [];
                    }
                }
                $currentSegment[] = ['index' => $index, 'value' => (float) $m->weight_kg];
                $prevDate = $m->measured_date;
            }
            if (! empty($currentSegment)) {
                $segments[] = $currentSegment;
            }

            $total = $measurements->count();
            $datasets = [];
            foreach ($segments as $segment) {
                // 全ラベルに対応する配列。値のある位置だけ数値を入れ、他は null。
                $data = array_fill(0, $total, null);
                foreach ($segment as $point) {
                    $data[$point['index']] = $point['value'];
                }
                $datasets[] = ['data' => $data];
            }

            $charts[] = [
                'id' => $trainee->id,
                'name' => $trainee->name,
                'labels' => $labels,
                'tooltips' => $tooltips,
                'datasets' => $datasets,
            ];
        }

        return $charts;
    }
}
