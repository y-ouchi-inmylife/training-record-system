@extends('layouts.client')

@section('title', 'メールアドレスの変更')

@section('content')
<div class="container">
    <div class="c-settings">
        {{-- 戻る導線：他画面（training-records/show）と同じ .c-detail-back パターン --}}
        <a href="{{ route('client-portal.profile.show') }}" class="c-detail-back">
            <span aria-hidden="true">←</span> 登録情報
        </a>

        <h1 class="mb-4">メールアドレスの変更</h1>

        <div class="card mb-4">
            <div class="card-body p-4">
                {{-- 補足説明：確認メール経由の流れは珍しいので、画面内で流れを説明する --}}
                <p class="text-muted mb-4">
                    新しいアドレスに確認メールを送ります。メールのリンクを開くと、メールアドレスが切り替わります。
                </p>

                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="mb-3">
                    <div class="text-muted small">現在のメールアドレス</div>
                    <div class="font-monospace">{{ $client->email }}</div>
                </div>

                <form method="POST" action="{{ route('client-portal.settings.email.request') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="new_email" class="form-label">新しいメールアドレス <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('new_email') is-invalid @enderror"
                               id="new_email" name="new_email" required maxlength="255"
                               value="{{ old('new_email') }}"
                               autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label for="email_current_password" class="form-label">現在のパスワード <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                               id="email_current_password" name="current_password" required
                               autocomplete="current-password">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">確認メールを送る</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
