@extends('layouts.print')

@section('title', 'メールアドレス登録のご案内')

@section('content')
{{-- ページ上部の「クライアント詳細に戻る」導線。
     発行直後の同タブ遷移で開いたときに詳細画面へ戻れるようにするためのもの。
     `d-print-none` で印刷時は非表示にする（お客様に渡す紙にトレーナー向けの
     導線は不要）。以前「要素の表示・非表示切替に @media print を使わない」
     と決めたが、画面には出して紙には出さないというトレーナー専用の要件で
     代替手段がないため、この一点のみ例外として認めている
     （設計書 S-0307 備考、api-design.md GET print エンドポイント参照）。 --}}
<div class="print-back-link d-print-none mb-3">
    <a href="{{ route('clients.show', $client) }}" class="btn btn-outline-secondary btn-sm">&laquo; クライアント詳細に戻る</a>
</div>

@if($url)
    {{-- 有効な URL あり ---}}
    <h1 class="print-title">メールアドレスの登録のご案内</h1>

    <div class="print-qr">
        <canvas data-qr-url="{{ $url }}" data-qr-size="240"
                aria-label="メールアドレス登録用 URL の QR コード"></canvas>
    </div>

    <p class="print-lead">
        上の QR コードを読み取るか、下の URL をブラウザに入力してください。
    </p>

    <div class="print-url">{{ $url }}</div>

    <hr class="print-divider">

    <p class="print-section-title">ご登録の手順</p>
    <ol class="print-steps">
        <li>上の QR コードを読み取ります</li>
        <li>開いたページでメールアドレスを入力します</li>
        <li>届いたメールのリンクを開いて、パスワードを設定します</li>
    </ol>

    <hr class="print-divider">

    <p class="print-expiry-label">有効期限</p>
    <p class="print-expiry-date">{{ $expiresOn->format('Y年n月j日') }}まで</p>

    {{-- 訓練所名は config('app.client_portal_company')。未設定なら出力しない --}}
    @if(config('app.client_portal_company'))
        <p class="print-company">{{ config('app.client_portal_company') }}</p>
    @endif
@else
    {{-- 有効な URL なし。ページ上部の「クライアント詳細に戻る」リンクで
         詳細画面へ戻れるため、専用の CTA ボタンは置かない
         （両状態で戻り導線の見せ方を揃える）。--}}
    <h1 class="print-title">発行済みの有効なメールアドレス登録用 URL がありません</h1>
    <p class="print-lead">
        クライアント詳細画面から発行してください。
    </p>
@endif
@endsection

@if($url)
    @push('scripts')
        @vite(['resources/js/qr-code.js'])
    @endpush
@endif
