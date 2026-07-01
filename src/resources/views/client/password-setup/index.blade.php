@extends('layouts.client-public')

@section('title', 'パスワードの設定 - トレーニング記録閲覧')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h4 class="card-title text-center mb-1">トレーニング記録閲覧</h4>
                <p class="text-center text-muted mb-4">パスワードの設定</p>

                @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
                @endif

                <div class="mb-3">
                    <label class="form-label text-muted small">メールアドレス</label>
                    <div class="form-control-plaintext">{{ $client->email }}</div>
                </div>

                <form method="POST" action="{{ route('client-portal.password-setup.store', ['token' => $token]) }}">
                    @csrf

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
                        >
                        <div class="form-text small">8文字以上で、大文字・小文字・数字・記号をそれぞれ1文字以上含む必要があります。</div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">パスワード（確認）</label>
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
                        <button type="submit" class="btn btn-primary">パスワードを設定</button>
                    </div>
                </form>
            </div>
        </div>
        <p class="text-center text-muted mt-3 small">
            &copy; {{ date('Y') }} トレーニング記録管理システム
        </p>
    </div>
</div>
@endsection
