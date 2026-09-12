@extends('layouts.print')

@section('title', 'メールアドレス登録のご案内')

@section('content')
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
    {{-- 有効な URL なし ---}}
    <h1 class="print-title">発行済みの有効なメールアドレス登録用 URL がありません</h1>
    <p class="print-lead">
        クライアント詳細画面から発行してください。
    </p>
    <div class="print-error-actions">
        <a href="{{ route('clients.show', $client) }}" class="btn btn-primary">クライアント詳細画面を開く</a>
    </div>
@endif
@endsection

@if($url)
    @push('scripts')
        @vite(['resources/js/qr-code.js'])
    @endpush
@endif
