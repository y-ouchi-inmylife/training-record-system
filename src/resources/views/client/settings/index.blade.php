@extends('layouts.client')

@php
    $prefectures = config('prefectures');
    // フォームごとにエラーバッグを分離（設計書 S-1406 の備考「エラーは対応する
    // フォームの上部にのみ表示」）。$errors->profile / ->password / ->email
    // で参照する。
    $profileErrors = $errors->hasBag('profile') ? $errors->profile : null;
    $passwordErrors = $errors->hasBag('password') ? $errors->password : null;
    $emailErrors = $errors->hasBag('email') ? $errors->email : null;
@endphp

@section('title', '登録情報')

@section('content')
<div class="container">
    <div class="c-settings">
        <h1 class="mb-4">登録情報</h1>

        {{-- 基本情報フォーム（電話番号・住所）--}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <p class="eyebrow">── 基本情報 ──</p>

                @if(session('profile_success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('profile_success') }}
                    </div>
                @endif

                @if($profileErrors && $profileErrors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach($profileErrors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                {{-- お名前は表示のみ（変更は担当トレーナーに依頼）。案内文は添えない --}}
                <div class="mb-3">
                    <div class="text-muted small">お名前</div>
                    <div>
                        {{ $client->full_name }}@if($client->full_name_kana)<span class="text-muted small">（{{ $client->full_name_kana }}）</span>@endif
                    </div>
                </div>

                <form method="POST" action="{{ route('client-portal.settings.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-2">
                        <label for="phone1" class="form-label">電話番号 <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control @if($profileErrors && $profileErrors->has('phone1')) is-invalid @endif"
                               id="phone1" name="phone1" required maxlength="20"
                               value="{{ old('phone1', $client->phone1) }}">
                    </div>
                    <div class="mb-3">
                        <label for="phone2" class="form-label">予備の電話番号</label>
                        <input type="tel" class="form-control @if($profileErrors && $profileErrors->has('phone2')) is-invalid @endif"
                               id="phone2" name="phone2" maxlength="20"
                               value="{{ old('phone2', $client->phone2) }}">
                    </div>

                    <div class="mb-2">
                        <label for="postal_code" class="form-label">郵便番号 <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control @if($profileErrors && $profileErrors->has('postal_code')) is-invalid @endif"
                                   id="postal_code" name="postal_code" required
                                   value="{{ old('postal_code', $client->postal_code) }}"
                                   placeholder="123-4567">
                            <button type="button" class="btn btn-outline-secondary"
                                    id="btn-search-address" onclick="searchAddress()">検索</button>
                        </div>
                        {{-- address-search.js がここに検索の結果メッセージを書き込む（alert 非使用）--}}
                        <div id="address-search-message" class="form-text" role="status"></div>
                    </div>

                    <div class="mb-2">
                        <label for="address1" class="form-label">都道府県 <span class="text-danger">*</span></label>
                        <select class="form-select @if($profileErrors && $profileErrors->has('address1')) is-invalid @endif"
                                id="address1" name="address1" required>
                            <option value="">選択してください</option>
                            @foreach($prefectures as $pref)
                                <option value="{{ $pref }}" @selected(old('address1', $client->address1) === $pref)>{{ $pref }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="address2" class="form-label">市区町村 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @if($profileErrors && $profileErrors->has('address2')) is-invalid @endif"
                               id="address2" name="address2" required maxlength="50"
                               value="{{ old('address2', $client->address2) }}">
                    </div>
                    <div class="mb-2">
                        <label for="address3" class="form-label">町名・番地 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @if($profileErrors && $profileErrors->has('address3')) is-invalid @endif"
                               id="address3" name="address3" required maxlength="100"
                               value="{{ old('address3', $client->address3) }}">
                    </div>
                    <div class="mb-3">
                        <label for="address4" class="form-label">建物名・部屋番号</label>
                        <input type="text" class="form-control @if($profileErrors && $profileErrors->has('address4')) is-invalid @endif"
                               id="address4" name="address4" maxlength="100"
                               value="{{ old('address4', $client->address4) }}">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">基本情報を保存</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- メールアドレス変更フォーム --}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <p class="eyebrow">── メールアドレスの変更 ──</p>

                @if(session('email_success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('email_success') }}
                    </div>
                @endif

                @if($emailErrors && $emailErrors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach($emailErrors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="mb-3">
                    <div class="text-muted small">現在のメールアドレス</div>
                    <div class="font-monospace">{{ $client->email }}</div>
                </div>

                <form method="POST" action="{{ route('client-portal.settings.email-change.request') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="new_email" class="form-label">新しいメールアドレス <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @if($emailErrors && $emailErrors->has('new_email')) is-invalid @endif"
                               id="new_email" name="new_email" required maxlength="255"
                               value="{{ old('new_email') }}"
                               autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label for="email_current_password" class="form-label">現在のパスワード <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @if($emailErrors && $emailErrors->has('current_password')) is-invalid @endif"
                               id="email_current_password" name="current_password" required
                               autocomplete="current-password">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">確認メールを送る</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- パスワードの変更フォーム --}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <p class="eyebrow">── パスワードの変更 ──</p>

                @if(session('password_success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('password_success') }}
                    </div>
                @endif

                @if($passwordErrors && $passwordErrors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach($passwordErrors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('client-portal.settings.password.update') }}">
                    @csrf
                    @method('PUT')

                    {{-- パスワードマネージャー向け username（保存パスワードの紐付け先）--}}
                    <input type="email" name="username" value="{{ $client->email }}"
                           autocomplete="username" readonly tabindex="-1" aria-hidden="true"
                           class="visually-hidden">

                    <div class="mb-3">
                        <label for="pw_current" class="form-label">現在のパスワード <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @if($passwordErrors && $passwordErrors->has('current_password')) is-invalid @endif"
                               id="pw_current" name="current_password" required
                               autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label for="pw_new" class="form-label">新しいパスワード <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @if($passwordErrors && $passwordErrors->has('new_password')) is-invalid @endif"
                               id="pw_new" name="new_password" required
                               autocomplete="new-password" aria-describedby="pw_new_help">
                        <div id="pw_new_help" class="form-text">
                            8 文字以上で、大文字・小文字・数字・記号をそれぞれ 1 つ以上入れてください。
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="pw_new_confirm" class="form-label">新しいパスワード（確認） <span class="text-danger">*</span></label>
                        <input type="password" class="form-control"
                               id="pw_new_confirm" name="new_password_confirmation" required
                               autocomplete="new-password">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">パスワードを変更</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 画面下部の案内（設計書 §4-10）--}}
        <p class="text-muted small text-center mb-0">
            利用をやめたい場合は担当トレーナーにご連絡ください
        </p>
    </div>
</div>

{{-- 住所検索スクリプト（郵便番号 → 住所自動入力）--}}
@vite(['resources/js/address-search.js'])
@endsection
