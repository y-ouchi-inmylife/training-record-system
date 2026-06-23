@extends('layouts.app')

@section('title', 'トレーニング記録数推移')

@push('styles')
<style>
    /* 列幅を colgroup で制御するため fixed レイアウト */
    .statistics-table {
        table-layout: fixed;
    }

    /* データセル共通 */
    .statistics-table td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ヘッダーは折り返し許可 */
    .statistics-table th {
        white-space: normal;
        word-wrap: break-word;
        line-height: 1.3;
        vertical-align: middle;
    }

    /* 性別と年齢の区切り線 */
    .statistics-table .border-group-end {
        border-right: 2px solid #dee2e6 !important;
    }

    /* 2段ヘッダーの1段目（グループ名）の背景色 */
    .statistics-table thead tr:first-child th[colspan] {
        background-color: #e9ecef;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="container">
    <h2 class="mb-4">トレーニング記録数推移</h2>

    {{-- フィルター・表示切替エリア --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('statistics.clients') }}" class="row g-3 align-items-end">
                @if(auth()->user()->isAdmin())
                <div class="col-auto">
                    <label for="trainer_id" class="form-label">トレーナー</label>
                    <select class="form-select" id="trainer_id" name="trainer_id" onchange="this.form.submit()">
                        <option value="all" {{ $trainerId === 'all' ? 'selected' : '' }}>すべて</option>
                        @foreach($trainers as $trainer)
                            <option value="{{ $trainer->id }}" {{ (string)$trainerId === (string)$trainer->id ? 'selected' : '' }}>
                                {{ $trainer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-auto">
                    <label class="form-label">表示</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="view_type" id="view_fiscal" value="fiscal_year"
                                {{ $viewType === 'fiscal_year' ? 'checked' : '' }} onchange="this.form.submit()">
                            <label class="form-check-label" for="view_fiscal">年度</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="view_type" id="view_calendar" value="calendar_year"
                                {{ $viewType === 'calendar_year' ? 'checked' : '' }} onchange="this.form.submit()">
                            <label class="form-check-label" for="view_calendar">年</label>
                        </div>
                    </div>
                </div>
                {{-- 非表示フィールド: 期間選択をリセットしない --}}
                @if($selectedPeriod)
                    <input type="hidden" name="period" value="{{ $selectedPeriod }}">
                @endif
            </form>
        </div>
    </div>

    <p class="mb-2 text-end">※年齢・性別は初回トレーニング時点の情報で集計しています。</p>

    {{-- 年度別推移 / 年別推移 --}}
    <h5 class="mb-3">{{ $viewType === 'fiscal_year' ? '年度別' : '年別' }}推移</h5>
    <div class="table-responsive mb-4">
        <table class="table table-striped table-sm table-bordered mb-0 statistics-table">
            <colgroup>
                <col style="width: 100px;">{{-- 年度/年/月 --}}
                <col style="width: 70px;">{{-- のべトレーニング記録数 --}}
                <col style="width: 70px;">{{-- クライアント実人数 --}}
                <col style="width: 55px;">{{-- 男 --}}
                <col style="width: 55px;">{{-- 女 --}}
                <col style="width: 55px;">{{-- その他 --}}
                <col style="width: 55px;">{{-- 未入力 --}}
                <col style="width: 55px;">{{-- ～19 --}}
                <col style="width: 55px;">{{-- 20～29 --}}
                <col style="width: 55px;">{{-- 30～39 --}}
                <col style="width: 55px;">{{-- 40～49 --}}
                <col style="width: 55px;">{{-- 50～59 --}}
                <col style="width: 55px;">{{-- 60～69 --}}
                <col style="width: 55px;">{{-- 70～ --}}
                <col style="width: 55px;">{{-- 不明 --}}
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2" class="text-nowrap align-middle">{{ $viewType === 'fiscal_year' ? '年度' : '年' }}</th>
                    <th rowspan="2" class="text-end align-middle">のべ<br>トレーニング記録数</th>
                    <th rowspan="2" class="text-end align-middle border-group-end">クライアント<br>実人数</th>
                    <th colspan="4" class="text-center border-group-end">性別</th>
                    <th colspan="8" class="text-center">年齢</th>
                </tr>
                <tr>
                    <th class="text-nowrap text-end col-gender">男</th>
                    <th class="text-nowrap text-end col-gender">女</th>
                    <th class="text-nowrap text-end col-gender">その他</th>
                    <th class="text-nowrap text-end col-gender border-group-end">未入力</th>
                    <th class="text-nowrap text-end col-age">～19</th>
                    <th class="text-nowrap text-end col-age">20～29</th>
                    <th class="text-nowrap text-end col-age">30～39</th>
                    <th class="text-nowrap text-end col-age">40～49</th>
                    <th class="text-nowrap text-end col-age">50～59</th>
                    <th class="text-nowrap text-end col-age">60～69</th>
                    <th class="text-nowrap text-end col-age">70～</th>
                    <th class="text-nowrap text-end col-age">不明</th>
                </tr>
            </thead>
            <tbody>
                @forelse($periodData as $row)
                <tr>
                    <td class="text-nowrap">
                        {{ $row->period }}{{ $viewType === 'fiscal_year' ? '年度' : '年' }}
                    </td>
                    <td class="text-end">{{ number_format($row->total_records) }}</td>
                    <td class="text-end border-group-end">{{ number_format($row->unique_clients) }}</td>
                    <td class="text-end col-gender">{{ number_format($row->gender_male) }}</td>
                    <td class="text-end col-gender">{{ number_format($row->gender_female) }}</td>
                    <td class="text-end col-gender">{{ number_format($row->gender_other) }}</td>
                    <td class="text-end col-gender border-group-end">{{ number_format($row->gender_unknown) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_10s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_20s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_30s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_40s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_50s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_60s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_70plus) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_unknown) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="15" class="text-center text-muted">データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 月別推移 --}}
    <div class="d-flex align-items-center gap-3 mb-3">
        <h5 class="mb-0">月別推移</h5>
        @if($availablePeriods->isNotEmpty())
        <form method="GET" action="{{ route('statistics.clients') }}" class="d-inline-flex align-items-center">
            <input type="hidden" name="trainer_id" value="{{ $trainerId }}">
            <input type="hidden" name="view_type" value="{{ $viewType }}">
            <label for="period_select" class="form-label mb-0 me-2 text-nowrap">
                {{ $viewType === 'fiscal_year' ? '年度選択' : '年選択' }}:
            </label>
            <select class="form-select form-select-sm" id="period_select" name="period" style="width: auto;" onchange="this.form.submit()">
                @foreach($availablePeriods as $period)
                    <option value="{{ $period }}" {{ (string)$selectedPeriod === (string)$period ? 'selected' : '' }}>
                        {{ $period }}{{ $viewType === 'fiscal_year' ? '年度' : '年' }}
                    </option>
                @endforeach
            </select>
        </form>
        @endif
    </div>
    <div class="table-responsive mb-4">
        <table class="table table-striped table-sm table-bordered mb-0 statistics-table">
            <colgroup>
                <col style="width: 100px;">{{-- 年度/年/月 --}}
                <col style="width: 70px;">{{-- のべトレーニング記録数 --}}
                <col style="width: 70px;">{{-- クライアント実人数 --}}
                <col style="width: 55px;">{{-- 男 --}}
                <col style="width: 55px;">{{-- 女 --}}
                <col style="width: 55px;">{{-- その他 --}}
                <col style="width: 55px;">{{-- 未入力 --}}
                <col style="width: 55px;">{{-- ～19 --}}
                <col style="width: 55px;">{{-- 20～29 --}}
                <col style="width: 55px;">{{-- 30～39 --}}
                <col style="width: 55px;">{{-- 40～49 --}}
                <col style="width: 55px;">{{-- 50～59 --}}
                <col style="width: 55px;">{{-- 60～69 --}}
                <col style="width: 55px;">{{-- 70～ --}}
                <col style="width: 55px;">{{-- 不明 --}}
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2" class="text-nowrap align-middle">月</th>
                    <th rowspan="2" class="text-end align-middle">のべ<br>トレーニング記録数</th>
                    <th rowspan="2" class="text-end align-middle border-group-end">クライアント<br>実人数</th>
                    <th colspan="4" class="text-center border-group-end">性別</th>
                    <th colspan="8" class="text-center">年齢</th>
                </tr>
                <tr>
                    <th class="text-nowrap text-end col-gender">男</th>
                    <th class="text-nowrap text-end col-gender">女</th>
                    <th class="text-nowrap text-end col-gender">その他</th>
                    <th class="text-nowrap text-end col-gender border-group-end">未入力</th>
                    <th class="text-nowrap text-end col-age">～19</th>
                    <th class="text-nowrap text-end col-age">20～29</th>
                    <th class="text-nowrap text-end col-age">30～39</th>
                    <th class="text-nowrap text-end col-age">40～49</th>
                    <th class="text-nowrap text-end col-age">50～59</th>
                    <th class="text-nowrap text-end col-age">60～69</th>
                    <th class="text-nowrap text-end col-age">70～</th>
                    <th class="text-nowrap text-end col-age">不明</th>
                </tr>
            </thead>
            <tbody>
                @forelse($monthlyData as $row)
                <tr>
                    <td class="text-nowrap">{{ $row->month }}</td>
                    <td class="text-end">{{ number_format($row->total_records) }}</td>
                    <td class="text-end border-group-end">{{ number_format($row->unique_clients) }}</td>
                    <td class="text-end col-gender">{{ number_format($row->gender_male) }}</td>
                    <td class="text-end col-gender">{{ number_format($row->gender_female) }}</td>
                    <td class="text-end col-gender">{{ number_format($row->gender_other) }}</td>
                    <td class="text-end col-gender border-group-end">{{ number_format($row->gender_unknown) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_10s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_20s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_30s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_40s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_50s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_60s) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_70plus) }}</td>
                    <td class="text-end col-age">{{ number_format($row->age_unknown) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="15" class="text-center text-muted">データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
