<?php

namespace App\Http\Controllers;

use App\Models\TrainingRecord;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * レポートコントローラー
 *
 * トレーニング記録数推移画面（S-1101）の表示を担当する。
 */
class StatisticsController extends Controller
{
    /**
     * トレーニング記録数推移画面を表示
     */
    public function clients(Request $request)
    {
        // staff は自分のデータのみ閲覧可。trainer_id を強制的に自身の ID に。
        if (auth()->user()->role === 'staff') {
            $trainerId = (string) auth()->id();
        } else {
            $trainerId = $request->get('trainer_id', 'all');
        }
        $viewType = $request->get('view_type', 'fiscal_year');
        $selectedPeriod = $request->get('period');

        // 表示モード切替時にperiodを調整（年度⇔年）
        $previousViewType = session('statistics_view_type');
        if ($previousViewType && $previousViewType !== $viewType && $selectedPeriod) {
            if ($previousViewType === 'fiscal_year' && $viewType === 'calendar_year') {
                // 年度 → 年: 2025年度(2025/4〜2026/3) → 2026年
                $selectedPeriod = $selectedPeriod + 1;
            } elseif ($previousViewType === 'calendar_year' && $viewType === 'fiscal_year') {
                // 年 → 年度: 2026年 → 2025年度
                $selectedPeriod = $selectedPeriod - 1;
            }
        }
        session(['statistics_view_type' => $viewType]);

        // トレーナー一覧取得（表示順）
        $trainers = Trainer::practitioners()->orderBy('display_order')->orderBy('name')->get();

        // 年度別/年別推移データ（降順）
        $periodData = $this->getPeriodData($trainerId, $viewType);

        // 期間選択プルダウン用のデータ
        $availablePeriods = collect($periodData)->pluck('period');

        // 調整後のperiodが存在しない場合はデータがある最新の期間にフォールバック
        if (!$selectedPeriod || !$availablePeriods->contains($selectedPeriod)) {
            $selectedPeriod = !empty($periodData) ? $periodData[0]->period : null;
        }

        // 月別推移データ
        $monthlyData = $this->getMonthlyData($trainerId, $viewType, $selectedPeriod);

        return view('statistics.clients', compact(
            'trainers',
            'trainerId',
            'viewType',
            'periodData',
            'monthlyData',
            'selectedPeriod',
            'availablePeriods'
        ));
    }

    /**
     * 年度別/年別推移データを取得（降順）
     */
    private function getPeriodData(string $trainerId, string $viewType): array
    {
        $query = TrainingRecord::query();

        $this->applyTrainerFilter($query, $trainerId);

        if ($viewType === 'fiscal_year') {
            // 年度別（4月〜翌3月）
            $periodExpr = "CASE WHEN MONTH(training_date) >= 4 THEN YEAR(training_date) ELSE YEAR(training_date) - 1 END";
        } else {
            // 年別（1月〜12月）
            $periodExpr = "YEAR(training_date)";
        }

        $rows = $query->select(
                DB::raw("{$periodExpr} as period"),
                DB::raw('COUNT(*) as total_records'),
                DB::raw('COUNT(DISTINCT client_id) as unique_clients')
            )
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (object) [
                'period' => $row->period,
                'total_records' => $row->total_records,
                'unique_clients' => $row->unique_clients,
            ];
        }

        return $result;
    }

    /**
     * 月別推移データを取得（昇順）
     */
    private function getMonthlyData(string $trainerId, string $viewType, ?int $selectedPeriod): array
    {
        if (!$selectedPeriod) {
            return [];
        }

        $query = TrainingRecord::query();
        $this->applyTrainerFilter($query, $trainerId);

        if ($viewType === 'fiscal_year') {
            $startDate = $selectedPeriod . '-04-01';
            $endDate = ($selectedPeriod + 1) . '-03-31';

            $query->whereBetween('training_date', [$startDate, $endDate]);

            $data = $query->select(
                    DB::raw('YEAR(training_date) as year'),
                    DB::raw('MONTH(training_date) as month'),
                    DB::raw('COUNT(*) as total_records'),
                    DB::raw('COUNT(DISTINCT client_id) as unique_clients')
                )
                ->groupBy(DB::raw('YEAR(training_date)'), DB::raw('MONTH(training_date)'))
                ->get()
                ->keyBy(fn ($item) => $item->year . '-' . $item->month);

            // 4月〜翌3月の全月を生成
            $result = [];
            $months = array_merge(range(4, 12), range(1, 3));
            foreach ($months as $month) {
                $year = $month >= 4 ? $selectedPeriod : $selectedPeriod + 1;
                $key = $year . '-' . $month;

                $result[] = (object) [
                    'month' => $year . '年' . $month . '月',
                    'total_records' => $data[$key]->total_records ?? 0,
                    'unique_clients' => $data[$key]->unique_clients ?? 0,
                ];
            }
        } else {
            $query->whereYear('training_date', $selectedPeriod);

            $data = $query->select(
                    DB::raw('MONTH(training_date) as month'),
                    DB::raw('COUNT(*) as total_records'),
                    DB::raw('COUNT(DISTINCT client_id) as unique_clients')
                )
                ->groupBy(DB::raw('MONTH(training_date)'))
                ->get()
                ->keyBy('month');

            // 1月〜12月の全月を生成
            $result = [];
            for ($month = 1; $month <= 12; $month++) {
                $result[] = (object) [
                    'month' => $selectedPeriod . '年' . $month . '月',
                    'total_records' => $data[$month]->total_records ?? 0,
                    'unique_clients' => $data[$month]->unique_clients ?? 0,
                ];
            }
        }

        return $result;
    }

    /**
     * トレーナーフィルタを適用
     */
    private function applyTrainerFilter($query, string $trainerId): void
    {
        if ($trainerId !== 'all') {
            $query->where(function ($q) use ($trainerId) {
                $q->where('trainer1_id', $trainerId)
                  ->orWhere('trainer2_id', $trainerId);
            });
        }
    }
}
