@extends('layouts.client')

@section('title', 'ダッシュボード - トレーニング記録閲覧')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-4">{{ auth('client')->user()->full_name }} さん、ようこそ</h1>

    {{-- メディアギャラリー（S-1402） --}}
    <div class="card mb-3">
        {{-- クライアントナビと同じオレンジ（.bg-client-nav = #fd7e14）＋白文字。 --}}
        {{-- 「トレーニング記録」ヘッダー側は白のまま変えない。 --}}
        <div class="card-header bg-client-nav text-white">
            <h6 class="mb-0">メディア（{{ count($mediaItems) }}件）</h6>
        </div>
        <div class="card-body">
            @if(count($mediaItems) === 0)
                <div class="text-muted">メディアはありません。</div>
            @else
                {{-- メディアが多い場合の縦スクロール（training-records-scroll と同じ流儀） --}}
                <div class="media-gallery-scroll">
                <div class="row row-cols-2 row-cols-md-4 row-cols-xl-6 g-3" id="mediaGalleryGrid">
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
                                <div class="card-body p-2 small text-center">
                                    <div class="text-muted">{{ $m['trainingDate'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                </div>
            @endif
        </div>
    </div>

    {{-- トレーニング記録一覧（S-1402 記録表） --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">トレーニング記録（{{ $trainingRecords->count() }}件）</h6>
        </div>
        @if($trainingRecords->count() > 0)
            <div class="training-records-scroll">
                <table class="table table-hover table-sm mb-0 training-records-table">
                    <thead class="table-light">
                        <tr>
                            <th>日付</th>
                            <th>担当1</th>
                            <th>担当2</th>
                            <th>トレーニング内容</th>
                            <th>メディア</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trainingRecords as $record)
                            <tr style="cursor: pointer;"
                                onclick="location.href='{{ route('client-portal.training-records.show', $record) }}'">
                                <td>{{ $record->training_date->format('Y/m/d') }}</td>
                                <td>{{ $record->trainer1->name ?? '—' }}</td>
                                <td>{{ $record->trainer2->name ?? '—' }}</td>
                                <td>{{ $record->trainingType->name ?? '—' }}</td>
                                <td>{{ $record->media_records_count > 0 ? $record->media_records_count : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body">
                <p class="text-muted mb-0">トレーニング記録はありません</p>
            </div>
        @endif
    </div>
</div>

{{-- 記録表のスクロール＋ヘッダー固定スタイル（layouts.client に @stack が無いためインラインで定義） --}}
<style>
    /* メディアギャラリーの縦スクロール（1.5行分くらい＝2行目が少し覗く高さ）。 */
    /* 値は実機で覗き具合を見て微調整すること。 */
    .media-gallery-scroll {
        max-height: 340px;
        overflow-x: hidden;
        overflow-y: auto;
        /* スクロールバー分、右側に少し余白を確保してカードが詰まって見えないようにする */
        padding-right: 4px;
    }
    .media-gallery-scroll::-webkit-scrollbar {
        width: 8px;
    }
    .media-gallery-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .media-gallery-scroll::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    .media-gallery-scroll::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    .training-records-scroll {
        max-height: 200px;
        overflow-y: auto;
        overflow-x: auto;
    }
    .training-records-scroll::-webkit-scrollbar {
        width: 8px;
    }
    .training-records-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .training-records-scroll::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
    .training-records-scroll::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    .training-records-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background-color: #f8f9fa;
    }
    .training-records-table tbody td,
    .training-records-table thead th {
        padding-top: 0.65rem;
        padding-bottom: 0.65rem;
    }
</style>

{{-- 原寸ライトボックス（写真拡大・動画再生）— S-1404 と共用の汎用 partial --}}
@include('media-records._lightbox')
@endsection

@push('scripts')
<script>
// メディアサムネイルをクリック → /client/media/{id}/play で presigned URL を取得 → ライトボックス表示。
// S-1404（client/training-records/show.blade.php）と同じロジックを、
// ダッシュボード側のグリッド ID（mediaGalleryGrid）に対して適用する。
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('mediaGalleryGrid');
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
