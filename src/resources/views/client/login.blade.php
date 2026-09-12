@extends('layouts.client')

@php
    // プロダクト名は config 経由で供給(設計書 §9-1)。暫定値は「トレーニング記録」。
    // 会社名は env が未設定なら null になり、Blade 側でフッターごと出さない(§9-2)。
    $portalName = config('app.client_portal_name', 'トレーニング記録');
    $companyName = config('app.client_portal_company');
@endphp

@section('title', 'ログイン')

@section('content')
<div class="c-login">
    {{-- ワードマーク: プロダクト名(§9-1)
         将来ロゴ画像が確定した場合はワードマーク左に画像スロット(max-height 48px)を
         追加できるよう余白の設計を維持。今は画像なしのテキストのみ。 --}}
    <h1 class="c-login-wordmark">{{ $portalName }}</h1>

    <div class="card c-login-card">
        <div class="card-body p-4">
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('client-portal.login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">パスワード</label>
                    <input
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="d-grid">
                    {{-- 動詞は能動態(設計書「文体の基本」)。btn-primary は
                         SCSS の変数上書きで cobalt 面 + mat 文字になる --}}
                    <button type="submit" class="btn btn-primary">ログインする</button>
                </div>
            </form>
        </div>
    </div>

    {{-- カード下の 2 種類の案内（設計書 §4-3）：
         1. 「パスワードを忘れた方」を先に置く（S-1407 への文字リンク）
         2. 空行を挟み「初めてご利用の方は…」のヘルプ文を後に置く
         並び順の根拠：本画面は「初回設定を終えたお客様が 2 回目以降にログインする画面」
         の位置づけ。パスワード忘れの救済導線を先、初めて訪れた方向けのヘルプは後 --}}
    <p class="c-login-help">
        <a href="{{ route('client-portal.password-reset.request.show') }}" class="btn btn-link btn-sm">パスワードを忘れた方</a>
    </p>

    <p class="c-login-help">初めてご利用の方は、担当トレーナーから受け取った URL からお進みください</p>

    {{-- フッター(会社名/年): config('app.client_portal_company') が未設定なら
         ブロックごと出力しない(設計書 §9-2)。カードの位置は上余白基準なので、
         フッターの有無でレイアウトが崩れない。 --}}
    @if($companyName)
        <p class="c-login-footer">&copy; {{ date('Y') }} {{ $companyName }}</p>
    @endif
</div>
@endsection
