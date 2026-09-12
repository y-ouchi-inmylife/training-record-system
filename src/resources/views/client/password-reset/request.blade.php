@extends('layouts.client-public')

@php
    $portalName = config('app.client_portal_name', 'トレーニング記録');
    $companyName = config('app.client_portal_company');
    // 送信完了状態は $submitted が true のとき
    $isDone = !empty($submitted);
@endphp

@section('title', $isDone ? 'メールをお送りしました' : 'パスワードの再設定')

@section('content')
{{-- pre-auth シェル（ログイン画面と共用）。項目 1 つのため既定幅 26rem で足りる --}}
<div class="c-login">
    <h1 class="c-login-wordmark">{{ $portalName }}</h1>

    <div class="card c-login-card">
        <div class="card-body p-4">
            @if($isDone)
                {{-- 完了状態：登録の有無を出さない文言（決定事項 #1）。
                     入力したメールアドレスは表示しない（S-1405 とはここが異なる） --}}
                <h2 class="c-auth-heading">メールをお送りしました</h2>
                <p class="c-auth-lead">
                    ご登録のメールアドレスが確認できた場合は、パスワード再設定のご案内をお送りしました。メールをご確認ください。
                </p>
                <p class="c-auth-lead">
                    メールが届かない場合は、担当のトレーナーにご連絡ください。
                </p>

                <div class="d-grid">
                    <a href="{{ route('client-portal.login') }}"
                       class="btn btn-outline-secondary">← ログイン画面に戻る</a>
                </div>
            @else
                {{-- 入力状態 --}}
                <h2 class="c-auth-heading">パスワードの再設定</h2>
                <p class="c-auth-lead">
                    ご登録のメールアドレスを入力してください。
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach ($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('client-portal.password-reset.request.send') }}">
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
                            autocomplete="email"
                        >
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary">再設定のメールを送る</button>
                    </div>
                </form>

                <div class="text-center">
                    <a href="{{ route('client-portal.login') }}" class="btn btn-link btn-sm">← ログイン画面に戻る</a>
                </div>
            @endif
        </div>
    </div>

    @if($companyName)
        <p class="c-login-footer">&copy; {{ date('Y') }} {{ $companyName }}</p>
    @endif
</div>
@endsection
