@extends('layouts.client')

@php
    // 曜日の日本語表記（Carbon の dayOfWeek は 0=日 〜 6=土）
    $weekdaysJp = ['日', '月', '火', '水', '木', '金', '土'];
    $hero = $sessions->first();
@endphp

@section('content')
<div class="container py-4 c-dashboard">
    {{-- 挨拶（設計書 §6: 「ようこそ」→「こんにちは」に変更） --}}
    <h1 class="c-greeting">{{ auth('client')->user()->full_name }} さん、こんにちは</h1>

    {{-- 犬の名前・写真の予約領域（設計書 §8-4）。
         将来 Client に dog リレーションが入ったら hidden を外して差し込む。 --}}
    <div class="c-dog-placeholder" hidden></div>

    @if($sessions->isEmpty())
        {{-- 空状態（記録0件）— 設計書 §4-2 --}}
        <section class="c-section c-empty-state" aria-label="空状態">
            <p class="eyebrow">ここに届きます</p>
            <div class="c-empty-card">
                <p class="mb-0">
                    最初のトレーニングが記録されると、この場所に
                    日付・写真・トレーナーからのノートが順番に届きます。
                </p>
            </div>
        </section>
    @else
        {{-- hero: 最新のトレーニング（先頭1件） --}}
        @php $rec = $hero['record']; $media = $hero['media']; @endphp
        <section class="c-section" aria-labelledby="c-latest-heading">
            <p class="eyebrow" id="c-latest-heading">最新のトレーニング</p>

            <article class="c-session c-session--hero">
                <div class="c-date-block" aria-hidden="true">
                    {{-- ≥576px 用: 縦組み3行(月・日・曜日)。モバイルでは display:none --}}
                    <span class="c-date-month">{{ $rec->training_date->month }}月</span>
                    <span class="c-date-day num-tabular">{{ $rec->training_date->day }}</span>
                    <span class="c-date-weekday">{{ $weekdaysJp[$rec->training_date->dayOfWeek] }}</span>
                    {{-- <576px 用: 横1行版(設計書 §5-1 モバイル形態)。≥576px では display:none --}}
                    <span class="c-date-inline">{{ $rec->training_date->month }}月 {{ $rec->training_date->day }}日（{{ $weekdaysJp[$rec->training_date->dayOfWeek] }}）</span>
                </div>
                <div class="c-session-body">
                    <div class="c-session-content">
                    {{-- 日付をスクリーンリーダー向けに追加(視覚では日付ブロックが表示) --}}
                    <p class="visually-hidden">{{ $rec->training_date->format('Y年n月j日') }}（{{ $weekdaysJp[$rec->training_date->dayOfWeek] }}）のトレーニング</p>

                    @if(count($media) > 0)
                        @php
                            $shown = array_slice($media, 0, 4);
                            $extra = count($media) - count($shown);
                        @endphp
                        <div class="c-session-media c-session-media--hero" data-media-grid>
                            @foreach($shown as $m)
                                <div class="c-media-thumb"
                                     data-media-id="{{ $m['id'] }}"
                                     data-media-type="{{ $m['type'] }}"
                                     data-conversion-status="{{ $m['conversionStatus'] }}"
                                     data-display-title="{{ $m['displayTitle'] }}"
                                     role="button"
                                     tabindex="0"
                                     aria-label="{{ $m['type'] === 'photo' ? '写真を開く' : '動画を開く' }}: {{ $m['displayTitle'] }}">
                                    @if($m['thumbnailUrl'])
                                        <img src="{{ $m['thumbnailUrl'] }}" alt="{{ $m['displayTitle'] }}">
                                        @if($m['type'] === 'video')
                                            @include('media-records._video-play-overlay')
                                        @endif
                                    @else
                                        <span class="c-media-placeholder">{{ $m['type'] === 'photo' ? '写真' : '動画' }}</span>
                                    @endif
                                    @if($m['conversionStatus'] !== 'not_required' && $m['conversionStatus'] !== 'done')
                                        <span class="c-media-badge">準備中</span>
                                    @endif
                                </div>
                            @endforeach
                            @if($extra > 0)
                                <a href="{{ route('client-portal.training-records.show', $rec) }}" class="c-media-more" aria-label="残り{{ $extra }}枚を見る">+{{ $extra }}</a>
                            @endif
                        </div>
                    @endif

                    @if($rec->record_content)
                        <p class="c-session-note">{{ \Illuminate\Support\Str::limit($rec->record_content, 120, '…') }}</p>
                    @endif

                    <a href="{{ route('client-portal.training-records.show', $rec) }}" class="c-session-cta">
                        {{ empty($rec->record_content) ? '記録を見る' : '続きを読む' }} <span aria-hidden="true">→</span>
                    </a>
                    </div>
                </div>
            </article>
        </section>

        {{-- feed: これまでの記録（残り） --}}
        @if($sessions->count() > 1)
            <section class="c-section" aria-labelledby="c-past-heading">
                <p class="eyebrow" id="c-past-heading">これまでの記録</p>
                <p class="meta c-section-sub">これまで <span class="num-tabular">{{ $sessions->count() }}</span> 回のトレーニング</p>

                @foreach($sessions->skip(1) as $session)
                    @php $rec = $session['record']; $media = $session['media']; @endphp
                    <article class="c-session">
                        <div class="c-date-block" aria-hidden="true">
                            {{-- ≥576px 用: 縦組み3行(月・日・曜日)。モバイルでは display:none --}}
                            <span class="c-date-month">{{ $rec->training_date->month }}月</span>
                            <span class="c-date-day num-tabular">{{ $rec->training_date->day }}</span>
                            <span class="c-date-weekday">{{ $weekdaysJp[$rec->training_date->dayOfWeek] }}</span>
                            {{-- <576px 用: 横1行版(設計書 §5-1 モバイル形態)。≥576px では display:none --}}
                            <span class="c-date-inline">{{ $rec->training_date->month }}月 {{ $rec->training_date->day }}日（{{ $weekdaysJp[$rec->training_date->dayOfWeek] }}）</span>
                        </div>
                        <div class="c-session-body">
                            <div class="c-session-content">
                            <p class="visually-hidden">{{ $rec->training_date->format('Y年n月j日') }}（{{ $weekdaysJp[$rec->training_date->dayOfWeek] }}）のトレーニング</p>

                            @if(count($media) > 0)
                                @php
                                    $shown = array_slice($media, 0, 3);
                                    $extra = count($media) - count($shown);
                                @endphp
                                <div class="c-session-media" data-media-grid>
                                    @foreach($shown as $m)
                                        <div class="c-media-thumb"
                                             data-media-id="{{ $m['id'] }}"
                                             data-media-type="{{ $m['type'] }}"
                                             data-conversion-status="{{ $m['conversionStatus'] }}"
                                             data-display-title="{{ $m['displayTitle'] }}"
                                             role="button"
                                             tabindex="0"
                                             aria-label="{{ $m['type'] === 'photo' ? '写真を開く' : '動画を開く' }}: {{ $m['displayTitle'] }}">
                                            @if($m['thumbnailUrl'])
                                                <img src="{{ $m['thumbnailUrl'] }}" alt="{{ $m['displayTitle'] }}">
                                                @if($m['type'] === 'video')
                                                    @include('media-records._video-play-overlay')
                                                @endif
                                            @else
                                                <span class="c-media-placeholder">{{ $m['type'] === 'photo' ? '写真' : '動画' }}</span>
                                            @endif
                                            @if($m['conversionStatus'] !== 'not_required' && $m['conversionStatus'] !== 'done')
                                                <span class="c-media-badge">準備中</span>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if($extra > 0)
                                        <a href="{{ route('client-portal.training-records.show', $rec) }}" class="c-media-more" aria-label="残り{{ $extra }}枚を見る">+{{ $extra }}</a>
                                    @endif
                                </div>
                            @endif

                            @if($rec->record_content)
                                <p class="c-session-note">{{ \Illuminate\Support\Str::limit($rec->record_content, 70, '…') }}</p>
                            @endif

                            <a href="{{ route('client-portal.training-records.show', $rec) }}" class="c-session-cta">
                                {{ empty($rec->record_content) ? '記録を見る' : '続きを読む' }} <span aria-hidden="true">→</span>
                            </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    @endif
</div>

{{-- インライン通知(「準備中」等) 用の Bootstrap Toast。
     設計書 §6: alert() を廃止し、内部状態語彙(processing 等)を出さない
     お客様向け文言に置き換える。トーストは cobalt 面 + mat 文字(§2-1)。 --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="c-toast" class="toast align-items-center c-toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
        <div class="d-flex">
            <div class="toast-body" id="c-toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="閉じる"></button>
        </div>
    </div>
</div>

{{-- 原寸ライトボックス(写真拡大・動画再生) — S-1404 と共用の汎用 partial --}}
@include('media-records._lightbox')
@endsection

@push('scripts')
<script>
// メディアサムネイルをクリック → /client-portal/media/{id}/play で
// presigned URL を取得 → ライトボックス表示。
// 設計書 §6 に従い alert() を廃止し、「準備中」等の状態は Bootstrap Toast で
// お客様向けの言葉に置き換えて表示する(内部状態語 processing 等は出さない)。
document.addEventListener('DOMContentLoaded', function () {
    const grids = document.querySelectorAll('[data-media-grid]');
    if (grids.length === 0) return;

    const toastEl = document.getElementById('c-toast');
    const toastBody = document.getElementById('c-toast-body');

    function showToast(msg) {
        if (!toastEl || !toastBody || typeof bootstrap === 'undefined') return;
        toastBody.textContent = msg;
        bootstrap.Toast.getOrCreateInstance(toastEl).show();
    }

    async function openMedia(card) {
        const id = card.dataset.mediaId;
        const type = card.dataset.mediaType;
        const status = card.dataset.conversionStatus;
        const title = card.dataset.displayTitle || '';

        // 変換未完(pending/processing/error)はお客様向け文言で通知
        if (status !== 'not_required' && status !== 'done') {
            showToast('この写真/動画は準備中です。少ししてから開いてみてください。');
            return;
        }

        try {
            const res = await fetch('/client-portal/media/' + encodeURIComponent(id) + '/play', {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) throw new Error('写真/動画を開けませんでした。時間をおいて試してみてください。');
            const body = await res.json();
            const url = body.data && body.data.url;
            if (!url) throw new Error('写真/動画を開けませんでした。時間をおいて試してみてください。');
            if (typeof window.openLightbox !== 'function') {
                throw new Error('この画面ではまだ写真/動画を開けません。ページを再読み込みしてみてください。');
            }
            window.openLightbox(type === 'photo' ? 'IMG' : 'VIDEO', url, title);
        } catch (err) {
            showToast(err.message || '写真/動画を開けませんでした。');
        }
    }

    grids.forEach(function (grid) {
        grid.addEventListener('click', function (e) {
            // "+N" のリンクは通常のページ遷移として扱う
            if (e.target.closest('.c-media-more')) return;

            const card = e.target.closest('.c-media-thumb');
            if (!card) return;
            openMedia(card);
        });

        // キーボード操作: Enter / Space で開く
        grid.addEventListener('keydown', function (e) {
            const card = e.target.closest('.c-media-thumb');
            if (!card) return;
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openMedia(card);
            }
        });
    });
});
</script>
@endpush
