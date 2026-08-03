@extends('layouts.client-public')

@php
    // ログイン画面と同じ config キー(§9-1・§9-2)。新規キーは作らない。
    $portalName = config('app.client_portal_name', 'トレーニング記録');
    $companyName = config('app.client_portal_company');
@endphp

@section('title', 'パスワードを決めます - ' . $portalName)

@section('content')
{{-- .c-login-* シェルはログイン画面と共用(pre-auth 用の同一構造)。
     カード内の見出し・説明文は .c-auth-* で新規定義 --}}
<div class="c-login">
    <h1 class="c-login-wordmark">{{ $portalName }}</h1>

    <div class="card c-login-card">
        <div class="card-body p-4">
            {{-- 見出し・説明文(設計書 §6): ラベル+値の管理画面型ではなく、
                 宛先(メールアドレス)を含めた 1 文にほぐす --}}
            <h2 class="c-auth-heading">パスワードを決めます</h2>
            <p class="c-auth-lead">
                <strong class="c-auth-email">{{ $client->email }}</strong>
                でログインするためのパスワードです。
            </p>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('client-portal.password-setup.store', ['token' => $token]) }}">
                @csrf

                {{-- パスワードマネージャー(1Password / Chrome 保存等)が「どのアカウントの
                     パスワードか」を判別できるよう、username を持たせる。
                     Chrome の DOM 警告チェックは type="hidden" を
                     「ユーザー名フィールドあり」と数えないため、視覚的に隠した
                     実入力欄を置く(仕様の記述より実際のブラウザ挙動を優先)。
                     - Bootstrap の .visually-hidden(position:absolute + clip)
                       で視覚的にのみ非表示。display:none / visibility:hidden は
                       ブラウザが認識しないため使わない
                     - readonly: 飼い主に編集させない
                     - tabindex="-1": Tab で不可視のフォーカスに飛ばない
                     - aria-hidden="true": スクリーンリーダーはメール宛先を含む
                       上部のリード文(.c-auth-lead)で既に読める。二重読み上げ回避
                     - サーバー側(PasswordSetupController@storeByToken)は
                       validate(['password' => ...]) のみで username を読まず、
                       追加送信は既存バリデーションに影響しない --}}
                <input
                    type="email"
                    name="username"
                    value="{{ $client->email }}"
                    autocomplete="username"
                    readonly
                    tabindex="-1"
                    aria-hidden="true"
                    class="visually-hidden"
                >

                <div class="mb-3">
                    <label for="password" class="form-label">パスワード</label>
                    <input
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        required
                        autofocus
                        autocomplete="new-password"
                        aria-describedby="password-help"
                    >
                    {{-- 要件(バリデーションと同じ)は変えず、文体を「〜する必要があります」から
                         「〜入れてください」の依頼形に(設計書 §6 の文体方針) --}}
                    <div id="password-help" class="form-text">
                        8 文字以上で、大文字・小文字・数字・記号をそれぞれ 1 つ以上入れてください。
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">パスワード（確認用）</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="d-grid">
                    {{-- 動詞は能動態(設計書「文体の基本」)。ログイン画面「ログインする」と揃える --}}
                    <button type="submit" class="btn btn-primary">設定する</button>
                </div>
            </form>
        </div>
    </div>

    {{-- フッター: config('app.client_portal_company') が未設定なら
         ブロックごと出力しない(設計書 §9-2)。ログイン画面と同じ扱い --}}
    @if($companyName)
        <p class="c-login-footer">&copy; {{ date('Y') }} {{ $companyName }}</p>
    @endif
</div>
@endsection
