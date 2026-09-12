@extends('layouts.client-public')

@php
    $portalName = config('app.client_portal_name', 'トレーニング記録');
    $companyName = config('app.client_portal_company');
@endphp

@section('title', $title)

@section('content')
{{-- pre-auth の説明カード。設計書 client-portal-design-plan.md §4-8。
     メールアドレス登録用 URL・ログイン用リンク・その他の無効トークンで共通に使う --}}
<div class="c-login">
    <h1 class="c-login-wordmark">{{ $portalName }}</h1>

    <div class="card c-login-card">
        <div class="card-body p-4">
            <h2 class="c-auth-heading">{{ $title }}</h2>
            <p class="c-auth-lead">{{ $message }}</p>

            {{-- 次にできること（設計書 §4-8）: 「なぜ入れないか」だけで終わらせず、
                 「次にどうすればよいか」を 1 ブロック添える --}}
            <div class="c-next">
                <p class="eyebrow">次にできること</p>
                <ul class="c-next-list">
                    <li>担当のトレーナーに、新しい案内をお願いしてください</li>
                    <li>別のメールが届いていないか、受信箱を確認してください</li>
                </ul>
            </div>
        </div>
    </div>

    @if($companyName)
        <p class="c-login-footer">&copy; {{ date('Y') }} {{ $companyName }}</p>
    @endif
</div>
@endsection
