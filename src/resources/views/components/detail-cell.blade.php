{{--
    表示用マス（ラベル上・値下）

    引数:
      label     (string)                  ラベル。上段に小さく表示
      value     (string|int|float|null)   値。下段に表示。null・空文字は「—」

    スロット（省略可）:
      値に HTML を含めたい場合はスロットに書く。
      スロットがある場合は value より優先する。

    常にマスを描画し、値の有無で行高が変わらないよう min-height を持たせる。
--}}
@props([
    'label' => '',
    'value' => null,
])

<div class="col-6 col-md-4">
    <div class="text-muted small mb-1">{{ $label }}</div>
    <div style="min-height: 1.5rem;">
        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            {{ ($value === null || $value === '') ? '—' : $value }}
        @endif
    </div>
</div>
