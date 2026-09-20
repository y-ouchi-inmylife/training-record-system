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
     * datasets が空配列。Blade 側で `empty($chart['datasets'])` を判定して
     * 「まだ計測値がありません」の案内に切り替える。詳細は screen-design.md S-1402
     * 「体重推移」設計方針の 2026-09 変更参照）:
     * [
     *   [
     *     'id'       => (int) トレーニーID,
     *     'name'     => (string) トレーニー名,
     *     'photoUrl' => (?string) 写真の presigned URL または null,
     *     'datasets' => [                          // 常に 0 or 1 本（線の分割を廃止）
     *       [
     *         'data' => [                          // {x, y} オブジェクト配列（Chart.js の時間軸形式）
     *           ['x' => '2026-09-14T08:00:00', 'y' => 4.50],
     *           ['x' => '2026-09-14T18:00:00', 'y' => 4.55],
     *           ...
     *         ],
     *       ],
     *     ],
     *   ],
     *   ...
     * ]
     *
     * トレーニー 0 頭の会員では空配列を返し、Blade で `@if(!empty($weightCharts))` により
     * ブロックごと非表示になる（この挙動は変更なし）。
     *
     * ## 横軸の扱い（2026-09 変更、設計書 S-1402「横軸を時間軸に変更した経緯」参照）
     *
     * 従前は「計測があった日付をカテゴリとして等間隔に並べ、一定日数以上空いたら
     * 線を分割する」方式だったが、間隔が違う計測点が同じ幅で表示される問題のほうが
     * 重大だったため、時間軸（`type: 'time'`）に変更した。線は 1 本の連続した折れ線で
     * 結ぶ（分割ロジック・`chart_gap_split_days` 設定値・null 埋め処理はすべて廃止）。
     *
     * ## タイムゾーンの扱い
     *
     * `measured_date`（Y-m-d）と `measured_time`（HH:MM:SS）を naive な ISO 8601
     * 文字列（`YYYY-MM-DDTHH:MM:SS`、タイムゾーン指定なし）に連結する。UTC の `Z` や
     * `+HH:MM` オフセットは付けない。ブラウザ側の date-fns/Chart.js は naive 文字列を
     * **ローカル時間として解釈**するため、DB 上の値（アプリタイムゾーン基準で入っている）
     * がそのままの見た目で表示される。Carbon 経由で toIso8601String() 等を使うと
     * UTC 変換や `+09:00` オフセットが混入するため、あえて `format()` で手組みする。
     */
    private function buildWeightCharts($client): array
    {
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
                    'photoUrl' => $trainee->photo_url,
                    'datasets' => [],
                ];
                continue;
            }

            // 各計測を {x: ISO8601 ローカル, y: 体重} に変換する。
            // measured_time は 'HH:MM:SS' で保存されているため、substr(0, 8) で HH:MM:SS を
            // 抜き出す。Chart.js の tooltipFormat 側で「Y/n/j HH:mm」まで丸めて表示する
            // （表示形式は measurement-chart.js で指定）。
            $points = [];
            foreach ($measurements as $m) {
                $points[] = [
                    'x' => $m->measured_date->format('Y-m-d') . 'T' . substr($m->measured_time, 0, 8),
                    'y' => (float) $m->weight_kg,
                ];
            }

            $charts[] = [
                'id' => $trainee->id,
                'name' => $trainee->name,
                // トレーニー写真の presigned URL（写真未登録なら null）。
                // photo_url アクセサは trainee 1 件で 1 回だけ presigned URL を発行するため、
                // 追加のクエリは走らない（N+1 は発生しない。$client->trainees()->with('measurements')
                // で既にトレーニー本体は取得済みで、photo_path はそのカラム値を使うだけ）。
                'photoUrl' => $trainee->photo_url,
                // datasets は常に 1 本のみ（線の分割を廃止したため。設計書 S-1402
                // 「横軸を時間軸に変更した経緯」参照）。0 件のトレーニーは上の isEmpty
                // 早期リターンで datasets: [] を返しているためここには来ない。
                'datasets' => [
                    ['data' => $points],
                ],
            ];
        }

        return $charts;
    }
}
