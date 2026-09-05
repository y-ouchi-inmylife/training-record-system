@extends('layouts.app')

@section('title', 'トレーニング記録詳細')

@push('styles')
<style>
/* メディアセクション: 1行に横並びで配置し、収まらない場合は横スクロール。
   件数が増えてもセクションの高さは変わらない */
.media-scroll {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
}
.media-scroll > .media-card {
    flex: 0 0 160px;
    width: 160px;
}
</style>
@endpush

@section('content')
<div class="container">
    <div class="mb-4">
        {{-- 1段目: 操作ボタン群（右寄せ） --}}
        <div class="d-flex justify-content-end gap-2 mb-2">
            <a href="{{ route('training-records.index') }}" class="btn btn-outline-secondary">&laquo; トレーニング記録一覧へ戻る</a>
            <a href="{{ route('training-records.edit', $trainingRecord) }}" class="btn btn-primary">編集</a>
            @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('training-records.destroy', $trainingRecord) }}" class="d-inline"
                      onsubmit="return confirm('このトレーニング記録を削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">削除</button>
                </form>
            @endif
        </div>

        {{-- 2段目: 日付行 --}}
        <div class="d-flex align-items-baseline gap-2 flex-wrap mb-2">
            <h2 class="mb-0">
                {{ $trainingRecord->training_date->format('Y/m/d') }}@if($trainingRecord->training_time)<span class="text-muted fs-6 ms-2">{{ substr($trainingRecord->training_time, 0, 5) }}</span>@endif
            </h2>
            <a href="{{ route('clients.show', $trainingRecord->client_id) }}" class="ms-3 fs-3 fw-bold">{{ $trainingRecord->client->display_name }}</a>
            <div class="d-flex align-items-baseline gap-2 ms-3">
                <span class="text-muted small">内部ID</span>
                <span class="font-monospace fs-5">{{ $trainingRecord->client->internal_id }}</span>
            </div>
        </div>

        {{-- 3段目: 属性3列 --}}
        @php
            $trainerNames = implode('・', array_filter([
                $trainingRecord->trainer1?->name,
                $trainingRecord->trainer2?->name,
            ]));
        @endphp
        <div class="row g-3 mt-2 pt-2 border-top">
            <div class="col-md-3">
                <div class="text-muted small">トレーニング内容</div>
                <div style="min-height: 1.5rem;">{{ $trainingRecord->trainingType?->name }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">トレーニング内容（詳細）</div>
                <div style="min-height: 1.5rem;">{{ $trainingRecord->training_detail }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">担当</div>
                <div style="min-height: 1.5rem;">{{ $trainerNames }}</div>
            </div>
        </div>
    </div>

    {{-- メディア（ヘッダーサマリーの直下：設計書 S-0403） --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">メディア</h6>
        </div>
        <div class="card-body">
            @if(count($mediaItems) === 0)
                <div class="text-muted">この記録のメディアはありません。</div>
            @else
                <div class="media-scroll" id="mediaViewGrid">
                    @foreach($mediaItems as $m)
                        <div class="card media-card"
                             data-media-id="{{ $m['id'] }}"
                             data-media-type="{{ $m['type'] }}"
                             data-conversion-status="{{ $m['conversionStatus'] }}"
                             data-display-title="{{ $m['displayTitle'] }}"
                             style="cursor: pointer;" role="button" tabindex="0">
                            <div class="ratio ratio-1x1 bg-light d-flex align-items-center justify-content-center">
                                @if($m['thumbnailUrl'])
                                    <img src="{{ $m['thumbnailUrl'] }}" alt="{{ $m['displayTitle'] }}" class="img-fluid">
                                    {{-- 動画のときだけ中央に▶をオーバーレイ（写真・プレースホルダには出さない） --}}
                                    @if($m['type'] === 'video')
                                        @include('media-records._video-play-overlay')
                                    @endif
                                @else
                                    <span class="text-muted">{{ $m['type'] === 'photo' ? '写真' : '動画' }}</span>
                                @endif
                            </div>
                            <div class="card-body p-2 small">
                                <div class="text-truncate" title="{{ $m['displayTitle'] }}">{{ $m['displayTitle'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- トレーニング記録 --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">トレーニング記録 <span class="text-muted">（事実を客観的に記録）</span></h6>
        </div>
        <div class="card-body">
            @if($trainingRecord->record_content)
                <div>{!! nl2br(e($trainingRecord->record_content)) !!}</div>
            @else
                <div class="text-muted">未記入</div>
            @endif
        </div>
    </div>

    {{-- 所感 --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">所感 <span class="text-muted">（トレーナー間共有・クライアント非開示）</span></h6>
        </div>
        <div class="card-body">
            @if($trainingRecord->impression)
                <div>{!! nl2br(e($trainingRecord->impression)) !!}</div>
            @else
                <div class="text-muted">未記入</div>
            @endif
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
