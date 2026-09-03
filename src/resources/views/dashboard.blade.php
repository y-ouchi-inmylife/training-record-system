@extends('layouts.app')

@section('content')
<div class="container">
    {{-- ウェルカムメッセージ --}}
    <div class="alert alert-info mb-4">
        ようこそ、<strong>{{ Auth::user()->name }}</strong> さん。
        （権限: {{ Auth::user()->role_display_name }}）
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
