@extends('layouts.app')

@section('title', 'トレーニング記録詳細')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">トレーニング記録詳細</h2>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('clients.show', $trainingRecord->client_id) }}" class="btn btn-outline-secondary">&laquo; クライアント詳細に戻る</a>
            <a href="{{ route('training-records.edit', $trainingRecord) }}" class="btn btn-success">編集</a>
            @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('training-records.destroy', $trainingRecord) }}" class="d-inline"
                      onsubmit="return confirm('このトレーニング記録を削除しますか？')">
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
    </div>

    {{-- メディア（基本情報の直下：設計書 S-0403） --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-media" style="cursor: pointer;">
            <h6 class="mb-0">メディア</h6>
        </div>
        <div class="collapse show" id="section-media">
        <div class="card-body">
            @if(count($mediaItems) === 0)
                <div class="text-muted">この記録のメディアはありません。</div>
            @else
                <div class="row row-cols-2 row-cols-md-4 row-cols-xl-6 g-3" id="mediaViewGrid">
                    @foreach($mediaItems as $m)
                        <div class="col">
                            <div class="card h-100 media-card"
                                 data-media-id="{{ $m['id'] }}"
                                 data-media-type="{{ $m['type'] }}"
                                 data-conversion-status="{{ $m['conversionStatus'] }}"
                                 data-display-title="{{ $m['displayTitle'] }}"
                                 style="cursor: pointer;" role="button" tabindex="0">
                                <div class="ratio ratio-1x1 bg-light d-flex align-items-center justify-content-center">
                                    @if($m['thumbnailUrl'])
                                        <img src="{{ $m['thumbnailUrl'] }}" alt="{{ $m['displayTitle'] }}" class="img-fluid">
                                    @else
                                        <span class="text-muted">{{ $m['type'] === 'photo' ? '写真' : '動画' }}</span>
                                    @endif
                                </div>
                                <div class="card-body p-2 small">
                                    <div class="text-truncate" title="{{ $m['displayTitle'] }}">{{ $m['displayTitle'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        </div>
    </div>

    {{-- トレーニング内容 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-content" style="cursor: pointer;">
            <h6 class="mb-0">トレーニング内容</h6>
        </div>
        <div class="collapse show" id="section-content">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th class="text-muted" style="width:40%">トレーニング内容</th><td>{{ $trainingRecord->trainingType->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted">トレーニング内容（詳細）</th><td>{{ $trainingRecord->training_detail ?: '—' }}</td></tr>
                        <tr><th class="text-muted">フェーズ</th><td>{{ $trainingRecord->phase->name ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </div>

    {{-- トレーニング記録 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-record" style="cursor: pointer;">
            <h6 class="mb-0">トレーニング記録 <span class="text-muted">（事実を客観的に記録）</span></h6>
        </div>
        <div class="collapse show" id="section-record">
            <div class="card-body">
                @if($trainingRecord->record_content)
                    <div>{!! nl2br(e($trainingRecord->record_content)) !!}</div>
                @else
                    <div class="text-muted">未記入</div>
                @endif
            </div>
        </div>
    </div>

    {{-- 所感 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-impression" style="cursor: pointer;">
            <h6 class="mb-0">所感 <span class="text-muted">（トレーナー間共有・クライアント非開示）</span></h6>
        </div>
        <div class="collapse show" id="section-impression">
            <div class="card-body">
                @if($trainingRecord->impression)
                    <div>{!! nl2br(e($trainingRecord->impression)) !!}</div>
                @else
                    <div class="text-muted">未記入</div>
                @endif
            </div>
        </div>
    </div>

    {{-- 最終更新 --}}
    <div class="text-end text-muted small mb-3">
        最終更新: {{ $trainingRecord->updated_at->format('Y/m/d H:i') }} {{ $trainingRecord->updatedBy?->name ?? '—' }}
    </div>
</div>

{{-- 原寸ライトボックス（写真拡大・動画再生） --}}
@include('media-records._lightbox')
@endsection

@push('scripts')
<script>
// メディアサムネイルをクリック → play で presigned URL を取得 → ライトボックス表示
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('mediaViewGrid');
    if (!grid) return;

    grid.addEventListener('click', async function (e) {
        const card = e.target.closest('.media-card');
        if (!card) return;
        const id = card.dataset.mediaId;
        const type = card.dataset.mediaType;
        const status = card.dataset.conversionStatus;
        const title = card.dataset.displayTitle || '';

        // 変換未完（pending/processing/error）は先取りで弾く
        if (status !== 'not_required' && status !== 'done') {
            alert('現在このメディアは表示できません（変換状態: ' + status + '）。');
            return;
        }

        try {
            const res = await fetch('/api/media-records/' + encodeURIComponent(id) + '/play', {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) throw new Error('再生 URL の取得に失敗しました');
            const body = await res.json();
            const url = body.data && body.data.url;
            if (!url) throw new Error('URL が取得できませんでした');
            if (typeof window.openLightbox !== 'function') {
                throw new Error('ライトボックスが初期化されていません');
            }
            window.openLightbox(type === 'photo' ? 'IMG' : 'VIDEO', url, title);
        } catch (err) {
            alert(err.message || '再生に失敗しました');
        }
    });
});
</script>
@endpush
