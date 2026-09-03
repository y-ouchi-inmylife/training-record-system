@extends('layouts.client-public')

@php
    // ログイン画面と同じ config キー(§9-1・§9-2)。新規キーは作らない
    $portalName = config('app.client_portal_name', 'トレーニング記録');
    $companyName = config('app.client_portal_company');
@endphp

@section('title', $title)

@section('content')
{{-- .c-login-* シェルはログイン・パスワード設定と共用。命名は login 起源だが
     pre-auth の説明カード全般に汎用的に効かせている --}}
<div class="c-login">
    <h1 class="c-login-wordmark">{{ $portalName }}</h1>

    <div class="card c-login-card">
        <div class="card-body p-4">
            {{-- $title / $message は PasswordSetupController から渡される。
                 ケース(トークン不在/期限切れ/使用済み/レース)で内容が異なる。
                 この画面はエラー(飼い主の落ち度)ではなく状況の説明なので、
                 見出し色は cobalt(既存の .c-auth-heading)。ember は使わない --}}
            <h2 class="c-auth-heading">{{ $title }}</h2>
            <p class="c-auth-lead">{{ $message }}</p>

            {{-- 次にできること(設計書 §4-8): 「なぜ入れないか」だけで終わらせず、
                 「次にどうすればよいか」を 1 ブロック添える。
                 ケース別出し分けはしない(判断は報告書参照)。共通で有効な
                 2 つのアクションだけを提示する --}}
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
