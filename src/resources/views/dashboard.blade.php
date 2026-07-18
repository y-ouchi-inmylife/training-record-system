@extends('layouts.app')

@section('title', 'ダッシュボード')

@push('styles')
<style>
    /* ダッシュボードの機能カード（カード全体をクリック可能なリンクに） */
    .dashboard-link-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }
    .dashboard-link-card:hover {
        transform: translateY(-2px);
        /* Bootstrap の shadow-sm が !important 指定のため、ホバー時も !important で打ち消す */
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .dashboard-link-card:focus-visible {
        outline: 2px solid #0d6efd;
        outline-offset: 2px;
    }
</style>
@endpush

@section('content')
<div class="container">
    <h2 class="mb-4">ダッシュボード</h2>

    {{-- ウェルカムメッセージ --}}
    <div class="alert alert-info mb-4">
        ようこそ、<strong>{{ Auth::user()->name }}</strong> さん。
        （権限: {{ Auth::user()->role_display_name }}）
    </div>

    <div class="row mb-3">
        {{-- クライアント一覧 --}}
        <div class="col-md-4 mb-3">
            <a href="{{ route('clients.index') }}" class="text-decoration-none text-reset d-block h-100" aria-label="クライアント一覧画面へ移動">
                <div class="card h-100 shadow-sm dashboard-link-card">
                    <div class="card-body">
                        <h5 class="card-title">クライアント一覧</h5>
                        <p class="card-text text-muted mb-0">クライアントの検索・閲覧</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- クライアント登録 --}}
        <div class="col-md-4 mb-3">
            <a href="{{ route('clients.create') }}" class="text-decoration-none text-reset d-block h-100" aria-label="クライアント登録画面へ移動">
                <div class="card h-100 shadow-sm dashboard-link-card">
                    <div class="card-body">
                        <h5 class="card-title">クライアント登録</h5>
                        <p class="card-text text-muted mb-0">クライアントの登録</p>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <div class="row mb-5">
        {{-- 録音 --}}
        <div class="col-md-4 mb-3">
            <a href="{{ route('recording') }}" class="text-decoration-none text-reset d-block h-100" aria-label="録音準備画面へ移動">
                <div class="card h-100 shadow-sm dashboard-link-card">
                    <div class="card-body">
                        <h5 class="card-title">録音</h5>
                        <p class="card-text text-muted mb-0">トレーニングの録音</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- 音声記録一覧 --}}
        <div class="col-md-4 mb-3">
            <a href="{{ route('audio-records.index') }}" class="text-decoration-none text-reset d-block h-100" aria-label="音声記録一覧画面へ移動">
                <div class="card h-100 shadow-sm dashboard-link-card">
                    <div class="card-body">
                        <h5 class="card-title">音声記録一覧</h5>
                        <p class="card-text text-muted mb-0">音声の文字起こし・要約を管理</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- サマリー情報: 主担当クライアント一覧 --}}
    <div class="d-flex justify-content-between align-items-baseline mb-3">
        <h4 class="mb-0">主担当クライアント一覧</h4>
        @if($myClientsTotal > 0)
            <span class="text-muted">
                全{{ $myClientsTotal }}件
                @if($myClientsTotal > 10)
                    中10件を表示 |
                    <a href="{{ route('clients.index', ['primary_trainer_id' => Auth::id()]) }}">すべて表示</a>
                @endif
            </span>
        @endif
    </div>

    @if($myClients->isEmpty())
        <div class="alert alert-secondary">
            主担当のクライアントはありません
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>内部ID</th>
                        <th>名前</th>
                        <th>最終記録日</th>
                        <th>担当1</th>
                        <th>担当2</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($myClients as $client)
                        @php
                            $lastRecord = $client->trainingRecords->first();
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('clients.show', $client->id) }}" class="text-decoration-none">
                                    {{ $client->internal_id }}
                                </a>
                            </td>
                            <td>{{ $client->display_name }}</td>
                            <td>
                                @if($client->last_training_date && $lastRecord)
                                    <a href="{{ route('training-records.show', $lastRecord->id) }}" class="text-decoration-none">
                                        {{ \Carbon\Carbon::parse($client->last_training_date)->format('Y年m月d日') }}
                                    </a>
                                @else
                                    <span class="text-muted">トレーニング記録なし</span>
                                @endif
                            </td>
                            <td>
                                @if($lastRecord && $lastRecord->trainer1)
                                    {{ $lastRecord->trainer1->name }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($lastRecord && $lastRecord->trainer2)
                                    {{ $lastRecord->trainer2->name }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
