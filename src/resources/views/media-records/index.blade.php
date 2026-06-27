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
        {{-- レスポンシブグリッド（2列〜6列）。
             サムネイル生成済み（thumbnail_url あり）なら .ratio 内に <img>、
             それ以外（未生成・生成中・失敗・3b-2 未対応の動画）は今まで通りプレースホルダ表示。 --}}
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 g-3">
            @foreach($mediaRecords as $media)
                @php($thumbnailUrl = $mediaModalData[$media->id]['thumbnail_url'] ?? null)
                <div class="col">
                    <div class="card h-100 media-card" data-media-id="{{ $media->id }}" style="cursor: pointer;" role="button" tabindex="0">
                        <div class="ratio ratio-1x1 bg-light d-flex align-items-center justify-content-center">
                            @if($thumbnailUrl)
                                <img src="{{ $thumbnailUrl }}" alt="{{ $media->display_title }}" class="img-fluid">
                            @else
                                <span class="text-muted">
                                    {{ $media->type === \App\Models\MediaRecord::TYPE_PHOTO ? '写真' : '動画' }}
                                </span>
                            @endif
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
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            {{-- ヘッダー右側に主要アクション（更新・削除）+ × を集約。
                 システム他画面の流儀（タイトル左 / 操作ボタン右 / 削除は最右） に揃え、
                 × と重複していた「閉じる」ボタンは廃止した。 --}}
            <div class="modal-header">
                <h5 class="modal-title" id="mediaDetailModalLabel">メディア詳細</h5>
                {{-- ms-auto で右端に押し出す。Bootstrap の .modal-header は justify-content:space-between だが、
                     本来は .btn-close 単独に margin-left:auto を当てる前提のため、btn-close を div でまとめたケースでは
                     ms-auto を明示する方が確実。 --}}
                <div class="d-flex gap-2 align-items-center ms-auto">
                    <button type="button" class="btn btn-success" id="mediaUpdateBtn">更新</button>
                    <button type="button" class="btn btn-danger" id="mediaDeleteBtn">削除</button>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
            </div>
            <div class="modal-body">
                {{-- 左右2カラム（md 未満では Bootstrap の挙動で自動的に縦積み） --}}
                <div class="row g-3">
                    {{-- 左カラム：メディア表示エリア（JSで img / video / 非対応メッセージを差し込む） --}}
                    <div class="col-md-7">
                        <div id="mediaDisplayArea" class="text-center" style="min-height: 200px;"></div>
                    </div>

                    {{-- 右カラム：メタ情報 --}}
                    <div class="col-md-5">
                        {{-- メタ情報（表示のみ / 編集可能の混在）--}}
                        {{-- align-items-center で、入力欄のある行（表示名・クライアント）の dt を dd の縦中央に揃える --}}
                        <dl class="row mb-0 small align-items-center">
                            <dt class="col-sm-3">登録日時</dt>
                            <dd class="col-sm-9" id="mediaMetaCreatedAt"></dd>

                            <dt class="col-sm-3">表示名</dt>
                            <dd class="col-sm-9">
                                <input type="text" id="mediaEditTitle" class="form-control form-control-sm" maxlength="255" placeholder="未入力時は元ファイル名を表示">
                            </dd>

                            <dt class="col-sm-3">元ファイル名</dt>
                            <dd class="col-sm-9" id="mediaMetaOriginalFilename"></dd>

                            <dt class="col-sm-3">種別</dt>
                            <dd class="col-sm-9" id="mediaMetaType"></dd>

                            <dt class="col-sm-3">クライアント <span class="text-danger">*</span></dt>
                            <dd class="col-sm-9">
                                <select id="mediaEditClientId" class="form-select form-select-sm select2-client-modal" style="width: 100%;">
                                    <option value="">クライアントを検索...</option>
                                </select>
                            </dd>

                            <dt class="col-sm-3">登録者</dt>
                            <dd class="col-sm-9" id="mediaMetaTrainer"></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- メディア原寸ライトボックス（自前オーバーレイ・S-1302-M01 拡張） --}}
<div id="mediaLightbox" class="media-lightbox" hidden>
    <button type="button" class="media-lightbox-close" id="mediaLightboxClose" aria-label="閉じる">&times;</button>
    <div class="media-lightbox-content" id="mediaLightboxContent"></div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    /* 詳細モーダルの画像/動画の高さを制限。
       縦長メディアでも右カラムの情報・ボタンが画面外に追い出されないようにする。 */
    #mediaDisplayArea img,
    #mediaDisplayArea video {
        max-height: 70vh;
        object-fit: contain;
    }
    /* 写真は「クリックで拡大」を示唆 */
    #mediaDisplayArea img {
        cursor: zoom-in;
    }
    /* 動画プレビューは「クリックで再生（ライトボックスを開く）」を示唆 */
    #mediaDisplayArea .video-preview-wrapper {
        cursor: pointer;
    }

    /* 動画プレビュー（詳細モーダル内）：ファーストフレーム + 中央に再生アイコン */
    .video-preview-wrapper {
        position: relative;
        display: inline-block;
        line-height: 0; /* video 下の隙間を消す */
    }
    .video-play-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 5rem;
        height: 5rem;
        pointer-events: none; /* クリックは video（→ライトボックス起動）に届くようにする */
    }

    /* 原寸ライトボックス（自前オーバーレイ） */
    .media-lightbox {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.88);
        z-index: 1080; /* Bootstrap modal(1055) より上 */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        cursor: zoom-out; /* 背景クリックで閉じる示唆 */
    }
    .media-lightbox[hidden] {
        display: none;
    }
    .media-lightbox-content {
        cursor: default; /* メディア本体クリックでは閉じない */
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .media-lightbox-content img,
    .media-lightbox-content video {
        max-width: 95vw;
        max-height: 95vh;
        object-fit: contain;
        display: block;
    }
    .media-lightbox-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 3rem;
        height: 3rem;
        border: 0;
        border-radius: 50%;
        background-color: rgba(0, 0, 0, 0.55);
        color: #fff;
        font-size: 1.75rem;
        line-height: 1;
        cursor: pointer;
        z-index: 1; /* オーバーレイ内で最前面 */
    }
    .media-lightbox-close:hover {
        background-color: rgba(0, 0, 0, 0.85);
    }

    /* 写真の「全体表示↔原寸表示」トグル。
       原寸時は overflow:auto でスクロール可能にし、content に margin:auto を当てる。
       中央寄せに align-items/justify-content を使うと、画像が画面より大きいときに
       上端が切れて scroll で到達できない flex の罠が起きる。
       margin:auto は余り領域が無くなれば 0 になる（負にならない）ため、
       小さい画像は中央・大きい画像は上端から配置されてスクロールで全範囲到達できる。 */
    .media-lightbox:not(.is-actual-size) .media-lightbox-content img {
        cursor: zoom-in;
    }
    .media-lightbox.is-actual-size {
        overflow: auto;
    }
    .media-lightbox.is-actual-size .media-lightbox-content {
        margin: auto;
    }
    .media-lightbox.is-actual-size .media-lightbox-content img {
        max-width: none;
        max-height: none;
        width: auto;
        height: auto;
        cursor: zoom-out;
    }
</style>
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

    // 詳細モーダル用データ（id → メタ情報辞書）
    const mediaModalData = @json($mediaModalData ?? new \stdClass());

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

            // 表示可否は conversion_status で判定する：
            //   not_required（jpeg/png/mp4 など原本がそのまま表示可能）
            //   done（変換済み・display_path に変換後ファイルがセット済み）
            // これ以外（pending/processing/error）は display_path が未確定で
            // play が 409 を返すため、呼ぶ前に状態併記の文言を出す。
            const canDisplay =
                meta.conversion_status === 'not_required' ||
                meta.conversion_status === 'done';
            if (!canDisplay) {
                setDisplayAlert(
                    '現在このメディアは表示できません（変換状態: ' + meta.conversion_status + '）。',
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
                    // 動画は wrapper + video + 再生アイコン SVG。
                    // controls は外し、クリックで原寸ライトボックスに引き渡す（写真と操作を統一）。
                    // src 末尾に #t=0.1 を付け、Safari 等で黒画面になりがちな
                    // preload="metadata" でもファーストフレームが描画されるようにする
                    // （# 以降はサーバに送信されないため presigned URL の署名には影響しない）。
                    const wrapper = document.createElement('div');
                    wrapper.className = 'video-preview-wrapper';

                    const video = document.createElement('video');
                    video.src = url + '#t=0.1';
                    video.preload = 'metadata';
                    video.muted = true;
                    video.playsInline = true;
                    video.className = 'img-fluid';
                    wrapper.appendChild(video);

                    // 中央に「半透明の丸 + 白い▶」の再生アイコン
                    const svgNs = 'http://www.w3.org/2000/svg';
                    const svg = document.createElementNS(svgNs, 'svg');
                    svg.setAttribute('class', 'video-play-overlay');
                    svg.setAttribute('viewBox', '0 0 80 80');
                    svg.setAttribute('aria-hidden', 'true');
                    const circle = document.createElementNS(svgNs, 'circle');
                    circle.setAttribute('cx', '40');
                    circle.setAttribute('cy', '40');
                    circle.setAttribute('r', '38');
                    circle.setAttribute('fill', 'rgba(0,0,0,0.55)');
                    const triangle = document.createElementNS(svgNs, 'polygon');
                    triangle.setAttribute('points', '33,25 33,55 58,40');
                    triangle.setAttribute('fill', '#fff');
                    svg.appendChild(circle);
                    svg.appendChild(triangle);
                    wrapper.appendChild(svg);

                    displayArea.appendChild(wrapper);
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

    // 原寸ライトボックス：詳細モーダルの画像/動画クリックで開く（自前オーバーレイ）
    const lightboxEl = document.getElementById('mediaLightbox');
    const lightboxContent = document.getElementById('mediaLightboxContent');
    const lightboxCloseBtn = document.getElementById('mediaLightboxClose');

    function openLightbox(tagName, srcUrl, altText) {
        lightboxContent.innerHTML = '';
        lightboxEl.classList.remove('is-actual-size'); // 必ず全体表示から始める
        if (tagName === 'IMG') {
            const img = document.createElement('img');
            img.src = srcUrl;
            img.alt = altText || '';
            lightboxContent.appendChild(img);
        } else {
            // ライトボックスを開いた直後は再生せず、停止状態（ファーストフレーム表示）で待機。
            // srcUrl には詳細モーダル側で付けた #t=0.1 が含まれているのでそのまま使う。
            // これにより preload="metadata" でも Safari 等で黒画面にならずファーストフレームが描画される。
            // ユーザーが controls の再生ボタンを押すと 0.1 秒地点から再生開始。
            const video = document.createElement('video');
            video.src = srcUrl;
            video.controls = true;
            video.preload = 'metadata';
            video.playsInline = true;
            lightboxContent.appendChild(video);
        }
        lightboxEl.removeAttribute('hidden');
    }

    function closeLightbox() {
        if (lightboxEl.hasAttribute('hidden')) return;
        const video = lightboxContent.querySelector('video');
        if (video) {
            video.pause();
            video.removeAttribute('src');
            video.load();
        }
        lightboxContent.innerHTML = '';
        lightboxEl.classList.remove('is-actual-size'); // 次回オープン時に持ち越さない
        lightboxEl.setAttribute('hidden', '');
    }

    // 写真のみ：クリックで全体表示↔原寸表示をトグル。
    // 原寸に切り替えた瞬間、写真の中央が画面の中央に来るようスクロール位置を初期化する。
    function toggleActualSize() {
        const img = lightboxContent.querySelector('img');
        if (!img) return; // 動画は対象外
        const nowActual = lightboxEl.classList.toggle('is-actual-size');
        if (nowActual) {
            requestAnimationFrame(function() {
                lightboxEl.scrollLeft = (lightboxEl.scrollWidth - lightboxEl.clientWidth) / 2;
                lightboxEl.scrollTop = (lightboxEl.scrollHeight - lightboxEl.clientHeight) / 2;
            });
        }
    }

    // 詳細モーダルの mediaDisplayArea にイベント委譲。
    // 写真は img を直接クリック。動画は wrapper 内の video をクリック
    // （再生アイコン SVG は pointer-events:none なのでクリックは video に届く）。
    displayArea.addEventListener('click', function(e) {
        const target = e.target;
        if (target.tagName === 'IMG') {
            openLightbox('IMG', target.src, target.alt);
        } else if (target.tagName === 'VIDEO') {
            openLightbox('VIDEO', target.src, '');
        }
    });

    // 背景クリックで閉じる（メディア本体クリックは content の cursor:default + ここで弾く）
    lightboxEl.addEventListener('click', function(e) {
        if (e.target === lightboxEl) closeLightbox();
    });
    lightboxCloseBtn.addEventListener('click', closeLightbox);

    // ライトボックス内の写真クリックで全体↔原寸トグル（動画は無反応）
    lightboxContent.addEventListener('click', function(e) {
        if (e.target.tagName === 'IMG') toggleActualSize();
    });

    // Esc キーで閉じる。Bootstrap modal も Esc で閉じるため、
    // capture phase で先取りして stopPropagation し、詳細モーダルまで閉じないようにする。
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !lightboxEl.hasAttribute('hidden')) {
            e.stopPropagation();
            closeLightbox();
        }
    }, true);

    // 詳細モーダル自体が閉じられたときも、念のためライトボックスを閉じてクリーンアップ
    modalEl.addEventListener('hidden.bs.modal', function() {
        closeLightbox();
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
