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
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">トレーナー</label>
                        <select name="counselor_id" class="form-select">
                            <option value="">すべて</option>
                            @foreach($counselors as $counselor)
                                <option value="{{ $counselor->id }}" {{ request('counselor_id') == $counselor->id ? 'selected' : '' }}>
                                    {{ $counselor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">操作</label>
                        <select name="action" class="form-select">
                            <option value="">すべて</option>
                            @foreach(\App\Models\AccessLog::actionLabels() as $key => $label)
                                <option value="{{ $key }}" {{ request('action') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">開始日</label>
                        <input type="date" name="date_from" class="form-control" value="{{ old('date_from', request('date_from')) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">終了日</label>
                        <input type="date" name="date_to" class="form-control" value="{{ old('date_to', request('date_to')) }}">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> 検索
                    </button>
                    <a href="{{ route('access-logs.index') }}" class="btn btn-secondary">クリア</a>
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
                            <td>{{ $log->counselor?->name ?? '—' }}</td>
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
