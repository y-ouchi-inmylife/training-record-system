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
                {{-- 行1: 内部ID + 名前 + 主担当 --}}
                <div class="row g-3 mb-2">
                    <div class="col-md-3">
                        <div class="row g-2 align-items-center">
                            <label for="internal_id" class="col-md-auto col-form-label text-md-end form-label-fixed">内部ID</label>
                            <div class="col-12 col-md">
                                <input type="text" class="form-control" id="internal_id" name="internal_id"
                                       value="{{ request('internal_id') }}" placeholder="部分一致">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="row g-2 align-items-center">
                            <label for="keyword" class="col-md-auto col-form-label text-md-end form-label-fixed">名前</label>
                            <div class="col-12 col-md">
                                <input type="text" class="form-control" id="keyword" name="keyword"
                                       inputmode="text"
                                       value="{{ request('keyword') }}" placeholder="姓名・かなで検索（部分一致）">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row g-2 align-items-center">
                            <label for="primary_trainer_id" class="col-md-auto col-form-label text-md-end form-label-fixed">主担当</label>
                            <div class="col-12 col-md">
                                <select class="form-select" id="primary_trainer_id" name="primary_trainer_id">
                                    <option value="">すべて</option>
                                    @foreach($trainers as $trainer)
                                        <option value="{{ $trainer->id }}" {{ request('primary_trainer_id') == $trainer->id ? 'selected' : '' }}>
                                            {{ $trainer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 行2: 最終記録日（範囲） --}}
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="row g-2 align-items-center">
                            <label class="col-md-auto col-form-label text-md-end form-label-fixed">最終記録日</label>
                            <div class="col">
                                <input type="text" class="form-control datepicker" id="date_from" name="date_from"
                                       value="{{ old('date_from', request('date_from')) }}"
                                       placeholder="例: 2026-04-01"
                                       pattern="\d{4}-\d{2}-\d{2}"
                                       maxlength="10">
                            </div>
                            <div class="col-md-auto px-1">～</div>
                            <div class="col">
                                <input type="text" class="form-control datepicker" id="date_to" name="date_to"
                                       value="{{ old('date_to', request('date_to')) }}"
                                       placeholder="例: 2026-04-01"
                                       pattern="\d{4}-\d{2}-\d{2}"
                                       maxlength="10">
                            </div>
                        </div>
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
                    <th>最終記録日</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr style="cursor: pointer;" onclick="location.href='{{ route('clients.show', $client) }}'">
                        <td>{{ $client->internal_id }}</td>
                        <td>{{ $client->display_name }}</td>
                        <td class="text-muted">{{ $client->display_name_kana }}</td>
                        <td>{{ $client->estimated_age }}</td>
                        <td>{{ $client->gender }}</td>
                        <td>{{ $client->primaryTrainer?->name }}</td>
                        <td>{{ $client->last_training_date ? \Carbon\Carbon::parse($client->last_training_date)->format('Y/m/d') : '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">該当するクライアントがありません。</td>
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
