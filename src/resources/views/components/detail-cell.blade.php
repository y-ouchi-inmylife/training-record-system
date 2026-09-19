{{--
    表示用マス（ラベル上・値下）

    引数:
      label     (string)                  ラベル。上段に小さく表示
      value     (string|int|float|null)   値。下段に表示。null・空文字は何も表示しない（画面設計書 §2-4）
      cols      (string)                  列幅の Bootstrap クラス。既定値は
                                          `col-6 col-md-4`（モバイル 2 列 / `md` 以上 3 列）。
                                          4 列にしたい画面では `col-6 col-md-3` を渡す（例：
                                          S-0309 トレーニー詳細）。**会員詳細（S-0305）の
                                          連絡先セクションは既定値のままにする**（既存の表示を
                                          変えないため。設計書 S-0305 セクション4 参照）。

    スロット（省略可）:
      値に HTML を含めたい場合はスロットに書く。
      スロットがある場合は value より優先する。

    常にマスを描画し、値の有無で行高が変わらないよう min-height を持たせる。
--}}
@props([
    'label' => '',
    'value' => null,
    'cols' => 'col-6 col-md-4',
])

<div class="{{ $cols }}">
    <div class="text-muted small mb-1">{{ $label }}</div>
    <div style="min-height: 1.5rem;">
        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            {{ $value ?? '' }}
        @endif
    </div>
</div>
