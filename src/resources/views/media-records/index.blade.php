@extends('layouts.app')

@section('title', 'メディア一覧')

@section('content')
<div class="container">
    <h2 class="mb-3">メディア一覧</h2>

    {{-- 登録者フィルタ --}}
    <div class="d-flex align-items-center gap-2 mb-4">
        <label for="trainer-filter" class="form-label mb-0 text-nowrap">登録者:</label>
        <select id="trainer-filter" class="form-select" style="width: auto;">
            <option value="all" {{ $selectedTrainerId == 'all' ? 'selected' : '' }}>全員</option>
            @foreach($trainers as $trainer)
                <option value="{{ $trainer->id }}" {{ $selectedTrainerId == $trainer->id ? 'selected' : '' }}>
                    {{ $trainer->name }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- メディアグリッド --}}
    @if($mediaRecords->isEmpty())
        <div class="alert alert-info">
            データがありません。メディア登録から追加してください。
        </div>
    @else
        {{-- レスポンシブグリッド（2列〜6列）。サムネイル無しのプレースホルダ表示。
             サムネイル実装時は .ratio 内を <img> に差し替える素朴な構造。 --}}
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 g-3">
            @foreach($mediaRecords as $media)
                <div class="col">
                    <div class="card h-100 media-card" data-media-id="{{ $media->id }}" style="cursor: pointer;" role="button" tabindex="0">
                        <div class="ratio ratio-1x1 bg-light d-flex align-items-center justify-content-center">
                            <span class="text-muted">
                                {{ $media->type === \App\Models\MediaRecord::TYPE_PHOTO ? '写真' : '動画' }}
                            </span>
                        </div>
                        <div class="card-body p-2 small">
                            <div class="text-muted">{{ $media->created_at->format('Y/m/d H:i') }}</div>
                            <div class="text-truncate" title="{{ $media->display_title }}">{{ $media->display_title }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">
            {{ $mediaRecords->links() }}
        </div>
    @endif
</div>

{{-- 詳細モーダル（S-1302-M01） --}}
<div class="modal fade" id="mediaDetailModal" tabindex="-1" aria-labelledby="mediaDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mediaDetailModalLabel">メディア詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
            </div>
            <div class="modal-body">
                {{-- メディア表示エリア（JSで img / video / 非対応メッセージを差し込む） --}}
                <div id="mediaDisplayArea" class="text-center mb-3" style="min-height: 200px;"></div>

                {{-- メタ情報 --}}
                <dl class="row mb-0 small">
                    <dt class="col-sm-3">登録日時</dt>
                    <dd class="col-sm-9" id="mediaMetaCreatedAt"></dd>

                    <dt class="col-sm-3">クライアント</dt>
                    <dd class="col-sm-9" id="mediaMetaClient"></dd>

                    <dt class="col-sm-3">種別</dt>
                    <dd class="col-sm-9" id="mediaMetaType"></dd>

                    <dt class="col-sm-3">表示名</dt>
                    <dd class="col-sm-9" id="mediaMetaTitle"></dd>

                    <dt class="col-sm-3">元ファイル名</dt>
                    <dd class="col-sm-9" id="mediaMetaOriginalFilename"></dd>

                    <dt class="col-sm-3">登録者</dt>
                    <dd class="col-sm-9" id="mediaMetaTrainer"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                {{-- 更新・削除は次フェーズ。設計レイアウト保持のため disabled で配置 --}}
                <button type="button" class="btn btn-success" disabled title="次フェーズで実装">更新</button>
                <button type="button" class="btn btn-danger" disabled title="次フェーズで実装">削除</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 登録者フィルタの変更で trainer_id クエリを差し替えて再読み込み（ページはリセット）
    const trainerFilter = document.getElementById('trainer-filter');
    if (trainerFilter) {
        trainerFilter.addEventListener('change', function() {
            const value = this.value;
            const url = new URL(window.location.href);
            url.searchParams.delete('page');
            url.searchParams.set('trainer_id', value);
            window.location.href = url.toString();
        });
    }

    // 詳細モーダル用データ（id → メタ情報辞書）と表示可能MIMEリスト
    const mediaModalData = @json($mediaModalData ?? new \stdClass());
    const displayableMimes = @json(\App\Models\MediaRecord::BROWSER_DISPLAYABLE_MIME_TYPES);

    const modalEl = document.getElementById('mediaDetailModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const displayArea = document.getElementById('mediaDisplayArea');
    const metaCreatedAt = document.getElementById('mediaMetaCreatedAt');
    const metaClient = document.getElementById('mediaMetaClient');
    const metaType = document.getElementById('mediaMetaType');
    const metaTitle = document.getElementById('mediaMetaTitle');
    const metaOriginalFilename = document.getElementById('mediaMetaOriginalFilename');
    const metaTrainer = document.getElementById('mediaMetaTrainer');

    // メディア表示エリアに alert メッセージを差し込む
    function setDisplayAlert(text, level) {
        displayArea.innerHTML = '';
        const div = document.createElement('div');
        div.className = 'alert alert-' + level + ' mb-0';
        div.textContent = text;
        displayArea.appendChild(div);
    }

    // カードクリック → メタ情報セット → モーダル表示 → play fetch → メディア差し込み
    document.querySelectorAll('.media-card').forEach(function(card) {
        card.addEventListener('click', function() {
            const id = this.dataset.mediaId;
            const meta = mediaModalData[id];
            if (!meta) return;

            // メタ情報を埋める（XSS回避のため textContent）
            metaCreatedAt.textContent = meta.created_at || '';
            metaClient.textContent = meta.client_name || '（削除済み）';
            metaType.textContent = meta.type === 'photo' ? '写真' : (meta.type === 'video' ? '動画' : meta.type);
            metaTitle.textContent = meta.display_title || '';
            metaOriginalFilename.textContent = meta.original_filename || '';
            metaTrainer.textContent = meta.trainer_name || '（削除済み）';

            // 一旦「読み込み中」を出してからモーダルを開く
            setDisplayAlert('読み込み中…', 'secondary');
            modal.show();

            // play で presigned GET URL を取得
            fetch('/api/media-records/' + encodeURIComponent(id) + '/play', {
                headers: { 'Accept': 'application/json' }
            })
            .then(function(res) {
                if (!res.ok) { throw new Error('HTTP ' + res.status); }
                return res.json();
            })
            .then(function(body) {
                const url = body.data && body.data.url;
                if (!url) { throw new Error('URL欠落'); }

                // ブラウザ表示可能なMIMEのみ <img>/<video>。それ以外は非対応メッセージ。
                if (!displayableMimes.includes(meta.mime_type)) {
                    setDisplayAlert(
                        'このブラウザでは表示できない形式です（変換対応は今後）。MIME: ' + meta.mime_type,
                        'warning'
                    );
                    return;
                }

                displayArea.innerHTML = '';
                if (meta.type === 'photo') {
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = meta.display_title || '';
                    img.className = 'img-fluid';
                    displayArea.appendChild(img);
                } else {
                    const video = document.createElement('video');
                    video.src = url;
                    video.controls = true;
                    video.preload = 'metadata';
                    video.className = 'img-fluid';
                    displayArea.appendChild(video);
                }
            })
            .catch(function(e) {
                console.error(e);
                setDisplayAlert('メディアの取得に失敗しました。', 'danger');
            });
        });
    });

    // モーダルclose時にクリーンアップ（動画停止・次回ちらつき防止）
    modalEl.addEventListener('hidden.bs.modal', function() {
        const video = displayArea.querySelector('video');
        if (video) {
            video.pause();
            video.removeAttribute('src');
            video.load();
        }
        displayArea.innerHTML = '';
    });
});
</script>
@endpush
