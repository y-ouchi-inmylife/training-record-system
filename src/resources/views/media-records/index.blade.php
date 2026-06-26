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

                {{-- メタ情報（表示のみ / 編集可能の混在）--}}
                <dl class="row mb-0 small">
                    <dt class="col-sm-3">登録日時</dt>
                    <dd class="col-sm-9" id="mediaMetaCreatedAt"></dd>

                    <dt class="col-sm-3">クライアント <span class="text-danger">*</span></dt>
                    <dd class="col-sm-9">
                        <select id="mediaEditClientId" class="form-select form-select-sm select2-client-modal" style="width: 100%;">
                            <option value="">クライアントを検索...</option>
                        </select>
                    </dd>

                    <dt class="col-sm-3">種別</dt>
                    <dd class="col-sm-9" id="mediaMetaType"></dd>

                    <dt class="col-sm-3">表示名</dt>
                    <dd class="col-sm-9">
                        <input type="text" id="mediaEditTitle" class="form-control form-control-sm" maxlength="255" placeholder="未入力時は元ファイル名を表示">
                    </dd>

                    <dt class="col-sm-3">元ファイル名</dt>
                    <dd class="col-sm-9" id="mediaMetaOriginalFilename"></dd>

                    <dt class="col-sm-3">登録者</dt>
                    <dd class="col-sm-9" id="mediaMetaTrainer"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="mediaUpdateBtn">更新</button>
                <button type="button" class="btn btn-danger" id="mediaDeleteBtn">削除</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
    const metaType = document.getElementById('mediaMetaType');
    const metaOriginalFilename = document.getElementById('mediaMetaOriginalFilename');
    const metaTrainer = document.getElementById('mediaMetaTrainer');
    const editTitle = document.getElementById('mediaEditTitle');
    const editClientSelect = document.getElementById('mediaEditClientId');
    const updateBtn = document.getElementById('mediaUpdateBtn');
    const deleteBtn = document.getElementById('mediaDeleteBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // 編集中のメディアID（モーダル close 時にクリア）
    let currentMediaId = null;

    // モーダル内クライアント Select2 初期化（既存 audio upload-create と同型）
    $(editClientSelect).select2({
        theme: 'bootstrap-5',
        placeholder: 'クライアントを検索（内部ID、名前、かな）',
        allowClear: false,
        width: '100%',
        dropdownParent: $('#mediaDetailModal'),  // モーダル内z-index対策
        ajax: {
            url: '/api/clients/search',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        },
        minimumInputLength: 1,
        language: {
            inputTooShort: function () { return '1文字以上入力してください'; },
            noResults: function () { return '該当するクライアントが見つかりません'; },
            searching: function () { return '検索中...'; }
        }
    });

    // 対象メディアの client_id を Select2 の初期値としてセット（id 指定で /api/clients/search を叩く）
    function loadInitialClient(clientId) {
        if (!clientId) {
            $(editClientSelect).val(null).trigger('change');
            return;
        }
        $.ajax({
            url: '/api/clients/search',
            data: { id: clientId },
            dataType: 'json'
        }).then(function(data) {
            if (data.results && data.results.length > 0) {
                const c = data.results[0];
                const option = new Option(c.text, c.id, true, true);
                $(editClientSelect).append(option).trigger('change');
            }
        });
    }

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

            currentMediaId = id;

            // 表示のみ項目（XSS回避のため textContent）
            metaCreatedAt.textContent = meta.created_at || '';
            metaType.textContent = meta.type === 'photo' ? '写真' : (meta.type === 'video' ? '動画' : meta.type);
            metaOriginalFilename.textContent = meta.original_filename || '';
            metaTrainer.textContent = meta.trainer_name || '（削除済み）';

            // 編集可能項目（input/select の値をセット）
            editTitle.value = meta.title_raw || '';
            loadInitialClient(meta.client_id);

            // 一旦「読み込み中」を出してからモーダルを開く
            setDisplayAlert('読み込み中…', 'secondary');
            modal.show();

            // ブラウザ表示可能なMIMEのみ play を呼ぶ。
            // それ以外（heic/heif/mov等）は変換待ちで display_path が NULL のため
            // play が 409 を返す。混乱を避けるため、呼ぶ前に非対応メッセージを出す。
            // ※変換ロジック・convert API は2b以降のフェーズで追加予定。
            if (!displayableMimes.includes(meta.mime_type)) {
                setDisplayAlert(
                    'このブラウザでは表示できない形式です（変換対応は今後）。MIME: ' + meta.mime_type,
                    'warning'
                );
                return;
            }

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

    // モーダルclose時にクリーンアップ（動画停止・次回ちらつき防止・編集状態クリア）
    modalEl.addEventListener('hidden.bs.modal', function() {
        const video = displayArea.querySelector('video');
        if (video) {
            video.pause();
            video.removeAttribute('src');
            video.load();
        }
        displayArea.innerHTML = '';
        currentMediaId = null;
        editTitle.value = '';
        $(editClientSelect).val(null).trigger('change');
    });

    // 422等のエラーレスポンスからユーザー向けメッセージを組み立てる
    async function readErrorMessage(res, fallback) {
        try {
            const body = await res.json();
            if (body && body.errors) {
                return Object.values(body.errors).flat().join('\n');
            }
            if (body && body.message) { return body.message; }
        } catch (e) { /* ignore */ }
        return fallback;
    }

    // 更新ボタン: title・client_id を PUT
    updateBtn.addEventListener('click', async function() {
        if (!currentMediaId) return;
        const clientId = $(editClientSelect).val();
        const title = editTitle.value.trim();

        if (!clientId) { alert('クライアントを選択してください。'); return; }

        updateBtn.disabled = true;
        try {
            const res = await fetch('/media-records/' + encodeURIComponent(currentMediaId), {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    client_id: clientId,
                    title: title || null,
                }),
            });
            if (!res.ok) {
                throw new Error(await readErrorMessage(res, '更新に失敗しました。'));
            }
            modal.hide();
            window.location.reload();
        } catch (e) {
            console.error(e);
            alert(e.message || '更新に失敗しました。');
        } finally {
            updateBtn.disabled = false;
        }
    });

    // 削除ボタン: 確認 → DELETE（成功時 reload）
    deleteBtn.addEventListener('click', async function() {
        if (!currentMediaId) return;
        if (!confirm('このメディアを削除します。レコードとストレージ上のファイルがともに削除され、元に戻せません。よろしいですか?')) {
            return;
        }
        deleteBtn.disabled = true;
        try {
            const res = await fetch('/media-records/' + encodeURIComponent(currentMediaId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            if (!res.ok) {
                throw new Error(await readErrorMessage(res, '削除に失敗しました。'));
            }
            modal.hide();
            window.location.reload();
        } catch (e) {
            console.error(e);
            alert(e.message || '削除に失敗しました。');
        } finally {
            deleteBtn.disabled = false;
        }
    });
});
</script>
@endpush
