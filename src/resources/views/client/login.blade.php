@extends('layouts.client')

@section('title', 'ログイン - トレーニング記録閲覧')

@section('content')
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <img src="{{ asset('images/client-portal-logo.png') }}"
                             alt="トレーニング記録閲覧"
                             class="img-fluid"
                             style="max-height: 96px;">
                    </div>
                    <h4 class="card-title text-center mb-4">トレーニング記録閲覧</h4>

                    @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
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
                                autocomplete="email"
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
                            <button type="submit" class="btn btn-primary">ログイン</button>
                        </div>
                    </form>
                </div>
            </div>
            <p class="text-center text-muted mt-3 small">
                &copy; {{ date('Y') }} トレーニング記録管理システム
            </p>
        </div>
    </div>
</div>
@endsection
