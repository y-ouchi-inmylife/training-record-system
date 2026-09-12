@extends('layouts.client-public')

@php
    $portalName = config('app.client_portal_name', 'トレーニング記録');
    $companyName = config('app.client_portal_company');
@endphp

@section('title', 'パスワードの再設定')

@section('content')
{{-- pre-auth シェル。項目 2 つのため既定幅 26rem で足りる（設計書 §4-11） --}}
<div class="c-login">
    <h1 class="c-login-wordmark">{{ $portalName }}</h1>

    <div class="card c-login-card">
        <div class="card-body p-4">
            <h2 class="c-auth-heading">パスワードの再設定</h2>
            <p class="c-auth-lead">
                新しいパスワードを設定してください。
            </p>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('client-portal.password-reset.reset.save', ['token' => $token]) }}">
                @csrf

                <div class="mb-3">
                    <label for="new_password" class="form-label">新しいパスワード</label>
                    <input
                        type="password"
                        class="form-control @error('new_password') is-invalid @enderror"
                        id="new_password"
                        name="new_password"
                        required
                        autofocus
                        autocomplete="new-password"
                        aria-describedby="new_password_help"
                    >
                    {{-- 強度要件のヘルプ文（S-1403 初回設定と同じ文言）--}}
                    <div id="new_password_help" class="form-text">
                        8 文字以上で、大文字・小文字・数字・記号をそれぞれ 1 つ以上入れてください。
                    </div>
                </div>

                <div class="mb-3">
                    <label for="new_password_confirmation" class="form-label">新しいパスワード（確認）</label>
                    <input
                        type="password"
                        class="form-control"
                        id="new_password_confirmation"
                        name="new_password_confirmation"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">設定する</button>
                </div>
            </form>
        </div>
    </div>

    @if($companyName)
        <p class="c-login-footer">&copy; {{ date('Y') }} {{ $companyName }}</p>
    @endif
</div>
@endsection
