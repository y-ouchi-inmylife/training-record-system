@extends('layouts.app')

@section('title', 'クライアント一覧')

@section('content')
<div class="container">
    {{-- ヘッダー --}}
    <h2 class="mb-4">クライアント一覧</h2>

    {{-- 検索フォーム --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('clients.index') }}">
                @if ($errors->has('date_to') || $errors->has('date_from'))
                    <div class="alert alert-danger">{{ $errors->first('date_to') ?: $errors->first('date_from') }}</div>
                @endif
                <div class="row g-3">
                    <div class="col-md-2">
                        <label for="internal_id" class="form-label">内部ID</label>
                        <input type="text" class="form-control" id="internal_id" name="internal_id"
                               value="{{ request('internal_id') }}" placeholder="部分一致">
                    </div>
                    <div class="col-md-3">
                        <label for="keyword" class="form-label">名前</label>
                        <input type="text" class="form-control" id="keyword" name="keyword"
                               inputmode="text"
                               value="{{ request('keyword') }}" placeholder="姓名・かなで検索（部分一致）">
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">最終トレーニング日（開始）</label>
                        <input type="text" class="form-control datepicker" id="date_from" name="date_from"
                               value="{{ old('date_from', request('date_from')) }}"
                               placeholder="例: 2026-04-01"
                               pattern="\d{4}-\d{2}-\d{2}"
                               maxlength="10">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">最終トレーニング日（終了）</label>
                        <input type="text" class="form-control datepicker" id="date_to" name="date_to"
                               value="{{ old('date_to', request('date_to')) }}"
                               placeholder="例: 2026-04-01"
                               pattern="\d{4}-\d{2}-\d{2}"
                               maxlength="10">
                    </div>
                    <div class="col-md-2">
                        <label for="support_status_id" class="form-label">支援状態</label>
                        <select class="form-select" id="support_status_id" name="support_status_id">
                            <option value="">すべて</option>
                            @foreach($supportStatuses as $status)
                                <option value="{{ $status->id }}" {{ request('support_status_id') == $status->id ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="primary_counselor_id" class="form-label">主担当</label>
                        <select class="form-select" id="primary_counselor_id" name="primary_counselor_id">
                            <option value="">すべて</option>
                            @foreach($counselors as $counselor)
                                <option value="{{ $counselor->id }}" {{ request('primary_counselor_id') == $counselor->id ? 'selected' : '' }}>
                                    {{ $counselor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> 検索
                    </button>
                    <a href="{{ route('clients.index') }}" class="btn btn-secondary">クリア</a>
                </div>
            </form>
        </div>
    </div>

    {{-- 件数表示・登録ボタン --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">{{ $clients->total() }}件のクライアント</p>
        <a href="{{ route('clients.create') }}" class="btn btn-primary">新規登録</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>
                        <a href="{{ route('clients.index', array_merge(request()->query(), ['sort' => 'internal_id', 'direction' => request('sort') === 'internal_id' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                            内部ID
                            @if(request('sort') === 'internal_id')
                                {{ request('direction') === 'asc' ? '▲' : '▼' }}
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="{{ route('clients.index', array_merge(request()->query(), ['sort' => 'last_name', 'direction' => request('sort') === 'last_name' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                            名前
                            @if(request('sort') === 'last_name')
                                {{ request('direction') === 'asc' ? '▲' : '▼' }}
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="{{ route('clients.index', array_merge(request()->query(), ['sort' => 'last_name_kana', 'direction' => request('sort') === 'last_name_kana' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                            かな
                            @if(request('sort') === 'last_name_kana')
                                {{ request('direction') === 'asc' ? '▲' : '▼' }}
                            @endif
                        </a>
                    </th>
                    <th>年齢</th>
                    <th>性別</th>
                    <th>主担当</th>
                    <th>支援状態</th>
                    <th>最終トレーニング日</th>
                    <th>フェーズ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr class="position-relative" style="cursor: pointer;">
                        <td><a href="{{ route('clients.show', $client) }}" class="stretched-link text-decoration-none text-reset">{{ $client->internal_id }}</a></td>
                        <td>{{ $client->display_name }}</td>
                        <td class="text-muted">{{ $client->display_name_kana }}</td>
                        <td>{{ $client->estimated_age }}</td>
                        <td>{{ $client->gender }}</td>
                        <td>{{ $client->primaryTrainer?->name }}</td>
                        @if($client->supportStatus)
                            <td>
                                <span class="badge bg-secondary fs-6">{{ $client->supportStatus->name }}</span>
                            </td>
                        @else
                            <td><span class="text-muted small" style="opacity: 0.5;">未設定</span></td>
                        @endif
                        <td>{{ $client->last_consultation_date ? \Carbon\Carbon::parse($client->last_consultation_date)->format('Y/m/d') : '' }}</td>
                        <td>{{ $client->latest_phase_id ? ($phases[$client->latest_phase_id] ?? '') : '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">該当するクライアントがありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ページネーション --}}
    @if($clients->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $clients->links() }}
        </div>
    @endif
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
