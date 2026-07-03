{{-- 動画サムネイル用 再生アイコン(▶) オーバーレイの CSS。
     @once で全ページ 1 回だけ styles スタックに投入する。
     _video-play-overlay.blade.php（SVG 出力）から @include されるほか、
     JS で SVG を動的生成する画面（training-records/_form.blade.php の
     buildCard / buildModalCard）からも CSS のみ読み込む目的で使う。 --}}
@once
@push('styles')
<style>
    /*
     * .ratio の中で中央に 2.5rem の再生アイコン(▶)を配置する。
     * Bootstrap 5 の .ratio > * が子要素を absolute で 100% 充填するため、
     * width/height/top/left は !important で打ち消して 2.5rem 四方に固定する。
     * サイズは実機で覗き具合を見て微調整すること。
     * pointer-events: none でクリックはカード側（img / .media-card）に通す。
     */
    .video-play-overlay-sm {
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        width: 2.5rem !important;
        height: 2.5rem !important;
        transform: translate(-50%, -50%);
        pointer-events: none;
        z-index: 1;
    }
</style>
@endpush
@endonce
