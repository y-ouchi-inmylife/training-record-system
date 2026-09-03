@extends('layouts.app')

@section('title', 'トレーナー操作履歴')

@section('content')
<div class="container">
    <h2 class="mb-4">トレーナー操作履歴</h2>

    {{-- 検索フォーム --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('access-logs.index') }}">
                @if ($errors->has('date_to') || $errors->has('date_from'))
                    <div class="alert alert-danger">{{ $errors->first('date_to') ?: $errors->first('date_from') }}</div>
                @endif
                {{-- 行1: トレーナー + 操作 --}}
                <div class="row g-3 mb-2">
                    <div class="col-md-4">
                        <div class="row g-2 align-items-center">
                            <label for="trainer_id" class="col-md-auto col-form-label text-md-end form-label-fixed">トレーナー</label>
                            <div class="col-12 col-md">
                                <select class="form-select" id="trainer_id" name="trainer_id">
                                    <option value="">すべて</option>
                                    @foreach($trainers as $trainer)
                                        <option value="{{ $trainer->id }}" {{ request('trainer_id') == $trainer->id ? 'selected' : '' }}>
                                            {{ $trainer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row g-2 align-items-center">
                            <label for="action" class="col-md-auto col-form-label text-md-end form-label-fixed">操作</label>
                            <div class="col-12 col-md">
                                <select class="form-select" id="action" name="action">
                                    <option value="">すべて</option>
                                    @foreach(\App\Models\AccessLog::actionLabels() as $key => $label)
                                        <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 行2: 日付（範囲） --}}
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="row g-2 align-items-center">
                            <label class="col-md-auto col-form-label text-md-end form-label-fixed">日付</label>
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
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('access-logs.index') }}" class="btn btn-secondary">クリア</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> 検索
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ログ一覧 --}}
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>日時</th>
                        <th>トレーナー</th>
                        <th>操作</th>
                        <th>対象</th>
                        <th>IPアドレス</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('Y/m/d H:i:s') }}</td>
                            <td>{{ $log->trainer?->name ?? '—' }}</td>
                            <td>{{ $log->action_label }}</td>
                            <td>
                                @if($log->target_type && $log->target_id)
                                    {{ $log->target_label }} #{{ $log->target_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-muted">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">操作履歴がありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</div>
@endsection
