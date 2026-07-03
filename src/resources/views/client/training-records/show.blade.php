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
                        <tr><th class="text-muted">日付</th><td>{{ $trainingRecord->training_date->format('Y/m/d') }}</td></tr>
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

    {{-- メディア（screen-design S-1404 セクション2） --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">メディア</h6>
        </div>
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

{{-- 原寸ライトボックス（写真拡大・動画再生）— S-0403 と共用の汎用 partial --}}
@include('media-records._lightbox')
@endsection

@push('scripts')
<script>
// メディアサムネイルをクリック → /client/media/{id}/play で presigned URL を取得 → ライトボックス表示
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
            const res = await fetch('/client/media/' + encodeURIComponent(id) + '/play', {
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
