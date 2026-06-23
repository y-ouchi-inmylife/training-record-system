@extends('layouts.app')

@section('title', 'トレーニング記録一覧')

@section('content')
<div class="container">
    <h2 class="mb-4">トレーニング記録一覧</h2>

    {{-- 検索フォーム --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('training-records.index') }}">
                @if ($errors->has('date_to') || $errors->has('date_from'))
                    <div class="alert alert-danger">{{ $errors->first('date_to') ?: $errors->first('date_from') }}</div>
                @endif
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">内部ID</label>
                        <input type="text" name="internal_id" class="form-control"
                               value="{{ request('internal_id') }}"
                               placeholder="部分一致">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">名前</label>
                        <input type="text" name="name" class="form-control"
                               inputmode="text"
                               value="{{ request('name') }}"
                               placeholder="姓名・かなで検索（部分一致）">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">トレーニング日（開始）</label>
                        <input type="text" name="date_from" class="form-control datepicker"
                               value="{{ old('date_from', request('date_from')) }}"
                               placeholder="例: 2026-04-01"
                               pattern="\d{4}-\d{2}-\d{2}"
                               maxlength="10">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">トレーニング日（終了）</label>
                        <input type="text" name="date_to" class="form-control datepicker"
                               value="{{ old('date_to', request('date_to')) }}"
                               placeholder="例: 2026-04-01"
                               pattern="\d{4}-\d{2}-\d{2}"
                               maxlength="10">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">担当1、担当2</label>
                        <select name="trainer_id" class="form-select">
                            <option value="">すべて</option>
                            @foreach($trainers as $trainer)
                                <option value="{{ $trainer->id }}" {{ request('trainer_id') == $trainer->id ? 'selected' : '' }}>
                                    {{ $trainer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md">
                        <label class="form-label">キーワード（トレーニング記録・所感を検索）</label>
                        <input type="text" name="keyword" class="form-control" inputmode="text" value="{{ request('keyword') }}" maxlength="100" placeholder="キーワードを入力">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> 検索
                    </button>
                    <a href="{{ route('training-records.index') }}" class="btn btn-secondary">クリア</a>
                </div>
            </form>
        </div>
    </div>

    {{-- 検索結果 --}}
    <div class="mb-3">
        <p class="text-muted mb-0">{{ $records->total() }}件のトレーニング記録</p>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>
                        <a href="{{ route('training-records.index', array_merge(request()->query(), ['sort' => 'internal_id', 'direction' => request('sort') === 'internal_id' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                            内部ID
                            @if(request('sort') === 'internal_id')
                                {{ request('direction') === 'asc' ? '▲' : '▼' }}
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="{{ route('training-records.index', array_merge(request()->query(), ['sort' => 'client_name', 'direction' => request('sort') === 'client_name' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                            名前
                            @if(request('sort') === 'client_name')
                                {{ request('direction') === 'asc' ? '▲' : '▼' }}
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="{{ route('training-records.index', array_merge(request()->query(), ['sort' => 'training_date', 'direction' => request('sort') === 'training_date' && request('direction', 'desc') === 'desc' ? 'asc' : 'desc'])) }}" class="text-decoration-none text-dark">
                            トレーニング日
                            @if(request('sort', 'training_date') === 'training_date')
                                {{ request('direction', 'desc') === 'desc' ? '▼' : '▲' }}
                            @endif
                        </a>
                    </th>
                    <th>担当1</th>
                    <th>担当2</th>
                    <th>トレーニング内容</th>
                    <th>フェーズ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr class="position-relative" style="cursor: pointer;">
                        <td><a href="{{ route('training-records.show', $record) }}" class="stretched-link text-decoration-none text-reset">{{ $record->client->internal_id ?? '—' }}</a></td>
                        <td>{{ $record->client->display_name ?? '—' }}</td>
                        <td>{{ $record->training_date->format('Y/m/d') }}</td>
                        <td>{{ $record->trainer1->name ?? '—' }}</td>
                        <td>{{ $record->trainer2->name ?? '—' }}</td>
                        <td>{{ $record->trainingType->name ?? '—' }}</td>
                        <td>{{ $record->phase->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">該当するトレーニング記録がありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $records->links() }}
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr('.datepicker', {
            locale: 'ja',
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
        });
    });
</script>
@endpush
