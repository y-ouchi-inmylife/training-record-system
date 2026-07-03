{{-- 動画サムネイルの中央にオーバーレイ表示する 再生アイコン(▶)。
     半透明の黒丸 + 白い三角。media-records/index.blade.php 詳細モーダル用
     .video-play-overlay（5rem）と同じ SVG 意匠を、サムネカード用の
     .video-play-overlay-sm（2.5rem 相当）として流用する。
     CSS は _video-play-overlay-styles.blade.php 側に @once で持たせる。
     利用側は .ratio.ratio-1x1 の中で、動画かつサムネイル画像がある場合にだけ
     @include する（写真・プレースホルダには出さない）。 --}}
@include('media-records._video-play-overlay-styles')
<svg class="video-play-overlay-sm" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <circle cx="40" cy="40" r="38" fill="rgba(0,0,0,0.55)"/>
    <polygon points="33,25 33,55 58,40" fill="#fff"/>
</svg>
