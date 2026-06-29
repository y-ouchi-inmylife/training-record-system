{{-- メディア原寸ライトボックス（自前オーバーレイ）。
     S-1302 メディア一覧（media-records/index.blade.php）と S-0403 トレーニング記録詳細
     （training-records/show.blade.php）で共用する部品。
     ※ 現状は media-records/index.blade.php は自前のコピーを使い続けており、本 partial を
     利用するのは training-records/show.blade.php のみ。将来 index も本 partial に統合する
     refactor は別タスクで実施する。

     利用方法:
       @include('media-records._lightbox')
       // ... JS から:
       window.openLightbox('IMG', url, altText);  // 写真
       window.openLightbox('VIDEO', url, '');     // 動画
--}}

@once
@push('styles')
<style>
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

<div id="mediaLightbox" class="media-lightbox" hidden>
    <button type="button" class="media-lightbox-close" id="mediaLightboxClose" aria-label="閉じる">&times;</button>
    <div class="media-lightbox-content" id="mediaLightboxContent"></div>
</div>

@push('scripts')
<script>
// IIFE + window.openLightbox の idempotent 公開。
// 将来 media-records/index.blade.php でも本 partial を読み込んだ場合に二重定義しないよう、
// 既に window.openLightbox が定義済みなら何もしない。
// （※ JS コメント内に Blade ディレクティブ綴り「アットマーク+include」を書くと
//   Blade パーサが拾って ParseError になるため、表現を「読み込んだ」に置き換えている）
(function () {
    if (typeof window.openLightbox === 'function') return;

    document.addEventListener('DOMContentLoaded', function () {
        const lightboxEl = document.getElementById('mediaLightbox');
        const lightboxContent = document.getElementById('mediaLightboxContent');
        const lightboxCloseBtn = document.getElementById('mediaLightboxClose');
        if (!lightboxEl || !lightboxContent || !lightboxCloseBtn) return;

        function openLightbox(tagName, srcUrl, altText) {
            lightboxContent.innerHTML = '';
            lightboxEl.classList.remove('is-actual-size'); // 必ず全体表示から始める
            if (tagName === 'IMG') {
                const img = document.createElement('img');
                img.src = srcUrl;
                img.alt = altText || '';
                lightboxContent.appendChild(img);
            } else {
                // 動画は controls 付きで配置（再生はユーザー操作で開始）。
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
                requestAnimationFrame(function () {
                    lightboxEl.scrollLeft = (lightboxEl.scrollWidth - lightboxEl.clientWidth) / 2;
                    lightboxEl.scrollTop = (lightboxEl.scrollHeight - lightboxEl.clientHeight) / 2;
                });
            }
        }

        // 背景クリックで閉じる（メディア本体クリックは content の cursor:default + ここで弾く）
        lightboxEl.addEventListener('click', function (e) {
            if (e.target === lightboxEl) closeLightbox();
        });
        lightboxCloseBtn.addEventListener('click', closeLightbox);

        // ライトボックス内の写真クリックで全体↔原寸トグル（動画は無反応）
        lightboxContent.addEventListener('click', function (e) {
            if (e.target.tagName === 'IMG') toggleActualSize();
        });

        // Esc キーで閉じる。capture phase で先取りして他のモーダルへの伝播を防ぐ。
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !lightboxEl.hasAttribute('hidden')) {
                e.stopPropagation();
                closeLightbox();
            }
        }, true);

        // 公開（idempotent 判定で再定義はしない）
        window.openLightbox = openLightbox;
        window.closeLightbox = closeLightbox;
    });
})();
</script>
@endpush
@endonce
