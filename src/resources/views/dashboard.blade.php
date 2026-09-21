@extends('layouts.app')

@section('content')
<div class="container">
    {{-- ウェルカムメッセージ --}}
    <div class="alert alert-info mb-4">
        ようこそ、<strong>{{ Auth::user()->name }}</strong> さん。
        （権限: {{ Auth::user()->role_display_name }}）
    </div>

    {{-- 最近のトレーニング記録（S-0201 セクション「最近のトレーニング記録」、2026-09 追加）。
         過去 1 週間（今日を含む 7 日間、未来日は含めない）を日付の新しい順で表示。
         見出しのすぐ右に「すべて表示」（S-0402 へ）、行の右端に絞り込みセレクト
         （「すべて」/「主担当のみ」、既定は「すべて」）を配置する。セレクトの変更で自動送信。
         詳細な設計方針は screen-design.md S-0201「設計方針」参照。 --}}
    <div class="d-flex justify-content-between align-items-baseline mb-2">
        <div class="d-flex align-items-baseline gap-3">
            <h4 class="mb-0">最近のトレーニング記録</h4>
            <a href="{{ route('training-records.index') }}" class="text-decoration-none">すべて表示</a>
        </div>
        {{-- 絞り込みセレクト：onchange で自動送信。ボタンは持たない（参考画面と同じ流儀）。
             現状ダッシュボードには他のクエリパラメータは無いため、素直に recent だけを送る。 --}}
        <form method="GET" action="{{ route('dashboard') }}" class="mb-0">
            <select name="recent" class="form-select form-select-sm" style="width: auto;"
                    onchange="this.form.submit()">
                <option value="all" {{ $recentFilter === 'all' ? 'selected' : '' }}>すべて</option>
                <option value="primary" {{ $recentFilter === 'primary' ? 'selected' : '' }}>主担当のみ</option>
            </select>
        </form>
    </div>

    @if($recentRecords->isEmpty())
        <div class="alert alert-secondary mb-4">
            過去1週間のトレーニング記録はありません。
        </div>
    @else
        <div class="table-responsive mb-4">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>日付</th>
                        <th>名前</th>
                        <th>トレーニー</th>
                        <th>担当1</th>
                        <th>担当2</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentRecords as $record)
                        <tr style="cursor: pointer;" onclick="location.href='{{ route('training-records.show', $record) }}'">
                            <td>{{ $record->training_date->format('Y/m/d') }}</td>
                            <td>{{ $record->client->display_name ?? '—' }}</td>
                            <td>{{ $record->client->trainees_label ?? '' }}</td>
                            <td>{{ $record->trainer1->name ?? '—' }}</td>
                            <td>{{ $record->trainer2->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
