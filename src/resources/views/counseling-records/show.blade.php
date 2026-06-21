@extends('layouts.app')

@section('title', '相談記録詳細')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">相談記録詳細</h2>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('clients.show', $counselingRecord->client_id) }}" class="btn btn-outline-secondary">&laquo; クライアント詳細に戻る</a>
            <a href="{{ route('counseling-records.edit', $counselingRecord) }}" class="btn btn-success">編集</a>
            @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('counseling-records.destroy', $counselingRecord) }}" class="d-inline"
                      onsubmit="return confirm('この相談記録を削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">削除</button>
                </form>
            @endif
        </div>
    </div>

    {{-- 基本情報 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-basic" style="cursor: pointer;">
            <h6 class="mb-0">基本情報</h6>
        </div>
        <div class="collapse show" id="section-basic">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th class="text-muted" style="width:40%">クライアント</th><td>{{ $counselingRecord->client->display_name }}</td></tr>
                        <tr><th class="text-muted">相談日</th><td>{{ $counselingRecord->consultation_date->format('Y/m/d') }}</td></tr>
                        <tr><th class="text-muted">相談時刻</th><td>{{ $counselingRecord->consultation_time ?: '—' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th class="text-muted" style="width:40%">担当1</th><td>{{ $counselingRecord->counselor1->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted">担当2</th><td>{{ $counselingRecord->counselor2->name ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th class="text-muted" style="width:40%">参加状況</th><td>{{ $counselingRecord->attendance ?: '—' }}</td></tr>
                        <tr><th class="text-muted">参加形態</th><td>{{ $counselingRecord->consultation_format ?: '—' }} {{ $counselingRecord->consultation_format_detail ? '（' . $counselingRecord->consultation_format_detail . '）' : '' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- 参加者 --}}
            <h6 class="mt-3 mb-2">参加者（{{ $counselingRecord->participants->count() }}名）</h6>
            @if($counselingRecord->participants->count() > 0)
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>本人との関係</th>
                            <th>関係の詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($counselingRecord->participants as $participant)
                            <tr>
                                <td>{{ $participant->participant_type }}</td>
                                <td>{{ $participant->participant_detail ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted mb-0">参加者の登録はありません。</p>
            @endif
        </div>
        </div>
    </div>

    {{-- 相談内容 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-content" style="cursor: pointer;">
            <h6 class="mb-0">相談内容</h6>
        </div>
        <div class="collapse show" id="section-content">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th class="text-muted" style="width:40%">インテーク</th><td>{{ $counselingRecord->is_intake ? 'はい' : 'いいえ' }}</td></tr>
                        <tr><th class="text-muted">フォローアップ</th><td>{{ $counselingRecord->is_followup ? 'はい' : 'いいえ' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th class="text-muted" style="width:40%">相談内容</th><td>{{ $counselingRecord->consultationType->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted">相談内容（詳細）</th><td>{{ $counselingRecord->consultation_detail ?: '—' }}</td></tr>
                        <tr><th class="text-muted">フェーズ</th><td>{{ $counselingRecord->phase->name ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>

    {{-- 相談記録 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-record" style="cursor: pointer;">
            <h6 class="mb-0">相談記録 <span class="text-muted">（事実を客観的に記録）</span></h6>
        </div>
        <div class="collapse show" id="section-record">
            <div class="card-body">
                @if($counselingRecord->record_content)
                    <div>{!! nl2br(e($counselingRecord->record_content)) !!}</div>
                @else
                    <div class="text-muted">未記入</div>
                @endif
            </div>
        </div>
    </div>

    {{-- 所感 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-impression" style="cursor: pointer;">
            <h6 class="mb-0">所感 <span class="text-muted">（カウンセラー間共有・クライアント非開示）</span></h6>
        </div>
        <div class="collapse show" id="section-impression">
            <div class="card-body">
                @if($counselingRecord->impression)
                    <div>{!! nl2br(e($counselingRecord->impression)) !!}</div>
                @else
                    <div class="text-muted">未記入</div>
                @endif
            </div>
        </div>
    </div>

    {{-- 最終更新 --}}
    <div class="text-end text-muted small mb-3">
        最終更新: {{ $counselingRecord->updated_at->format('Y/m/d H:i') }} {{ $counselingRecord->updatedBy?->name ?? '—' }}
    </div>
</div>
@endsection
