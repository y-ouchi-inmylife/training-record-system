@extends('layouts.client-public')

@php
    // ログイン画面と同じ config キー（§9-1・§9-2）を流用
    $portalName = config('app.client_portal_name', 'トレーニング記録');
    $companyName = config('app.client_portal_company');
    // 完了状態は $submittedEmail が非 null のとき
    $isDone = !empty($submittedEmail);
@endphp

@section('title', $isDone ? 'メールをお送りしました' : 'メールアドレスの登録')

@section('content')
{{-- pre-auth シェル（ログイン・初回設定と共通）。入力状態と完了状態を同じ画面で切り替える --}}
<div class="c-login">
    <h1 class="c-login-wordmark">{{ $portalName }}</h1>

    <div class="card c-login-card">
        <div class="card-body p-4">
            @if($isDone)
                {{-- 完了状態 --}}
                <h2 class="c-auth-heading">メールをお送りしました</h2>
                <p class="c-auth-lead">
                    <strong class="c-auth-email">{{ $submittedEmail }}</strong> に
                    ログイン用のリンクをお送りしました。
                </p>
                <p class="c-auth-lead">
                    もしメールが届かない場合は、入力し直すこともできます。
                </p>

                {{-- 「入力し直す」は同 URL への GET（入力状態に戻す）--}}
                <div class="d-grid">
                    <a href="{{ route('client-portal.email-registration.show', ['token' => $token]) }}"
                       class="btn btn-outline-secondary">入力し直す</a>
                </div>
            @else
                {{-- 入力状態 --}}
                <h2 class="c-auth-heading">メールアドレスの登録</h2>
                <p class="c-auth-lead">
                    ご自身のメールアドレスを入力してください。
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach ($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('client-portal.email-registration.store', ['token' => $token]) }}">
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

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">登録して進む</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @if($companyName)
        <p class="c-login-footer">&copy; {{ date('Y') }} {{ $companyName }}</p>
    @endif
</div>
@endsection
