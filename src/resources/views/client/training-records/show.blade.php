@extends('layouts.client')

@section('title', 'トレーニング記録詳細')

@section('content')
<div class="container">
    <div class="mb-3">
        <a href="{{ route('client-portal.dashboard') }}" class="btn btn-outline-secondary btn-sm">&laquo; ダッシュボード</a>
    </div>

    <h2 class="mb-4">トレーニング記録詳細</h2>

    {{-- 基本情報 --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">基本情報</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th class="text-muted" style="width:40%">クライアント</th><td>{{ $trainingRecord->client->display_name }}</td></tr>
                        <tr><th class="text-muted">トレーニング日</th><td>{{ $trainingRecord->training_date->format('Y/m/d') }}</td></tr>
                        <tr><th class="text-muted">トレーニング時刻</th><td>{{ $trainingRecord->training_time ?: '—' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th class="text-muted" style="width:40%">担当1</th><td>{{ $trainingRecord->trainer1->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted">担当2</th><td>{{ $trainingRecord->trainer2->name ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- トレーニング内容（フェーズはクライアント非表示） --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">トレーニング内容</h6>
        </div>
        <div class="card-body">
            <table class="table table-borderless table-sm">
                <tr><th class="text-muted" style="width:20%">トレーニング内容</th><td>{{ $trainingRecord->trainingType->name ?? '—' }}</td></tr>
                <tr><th class="text-muted">トレーニング内容（詳細）</th><td>{{ $trainingRecord->training_detail ?: '—' }}</td></tr>
            </table>
        </div>
    </div>

    {{-- トレーニング記録 --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">トレーニング記録</h6>
        </div>
        <div class="card-body">
            @if($trainingRecord->record_content)
                <div>{!! nl2br(e($trainingRecord->record_content)) !!}</div>
            @else
                <div class="text-muted">未記入</div>
            @endif
        </div>
    </div>
</div>
@endsection
