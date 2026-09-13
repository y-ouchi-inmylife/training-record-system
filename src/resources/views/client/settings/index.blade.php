@extends('layouts.client')

@php
    $prefectures = config('prefectures');
    // 基本情報フォームは 1 画面 1 フォームのため、名前付きエラーバッグは使わない。
    // 完了メッセージは共通キー session('success') に統一して、layouts.client の
    // 共通受け皿で画面上部に表示する（設計書 §4-10 参照）。
@endphp

@section('title', '登録情報')

@section('content')
<div class="container">
    <div class="c-settings">
        <h1 class="mb-4">登録情報</h1>

        {{-- 入口カード：メールアドレスの変更（S-1409 へ）。
             順序：初回設定画面（S-1403）と前後関係を揃えるため、
             ログインに関わる項目（メール → パスワード）を先に置き、
             連絡先（基本情報）を最後に置く。 --}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <p class="eyebrow">── メールアドレス ──</p>

                <div class="mb-3">
                    <div class="text-muted small">現在のメールアドレス</div>
                    <div class="font-monospace">{{ $client->email }}</div>
                </div>

                <div class="text-end">
                    <a href="{{ route('client-portal.settings.email.edit') }}"
                       class="btn btn-outline-secondary">メールアドレスを変更する</a>
                </div>
            </div>
        </div>

        {{-- 入口カード：パスワードの変更（S-1410 へ） --}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <p class="eyebrow">── パスワード ──</p>

                <div class="text-end">
                    <a href="{{ route('client-portal.settings.password.edit') }}"
                       class="btn btn-outline-secondary">パスワードを変更する</a>
                </div>
            </div>
        </div>

        {{-- 基本情報フォーム（電話番号・住所）--}}
        <div class="card mb-4">
            <div class="card-body p-4">
                <p class="eyebrow">── 基本情報 ──</p>

                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach($errors->all() as $error)
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
                        <input type="tel" class="form-control @error('phone1') is-invalid @enderror"
                               id="phone1" name="phone1" required maxlength="20"
                               value="{{ old('phone1', $client->phone1) }}">
                    </div>
                    <div class="mb-3">
                        <label for="phone2" class="form-label">電話番号（予備）</label>
                        <input type="tel" class="form-control @error('phone2') is-invalid @enderror"
                               id="phone2" name="phone2" maxlength="20"
                               value="{{ old('phone2', $client->phone2) }}">
                    </div>

                    <div class="mb-2">
                        <label for="postal_code" class="form-label">郵便番号 <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control @error('postal_code') is-invalid @enderror"
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
                        <select class="form-select @error('address1') is-invalid @enderror"
                                id="address1" name="address1" required>
                            <option value="">選択してください</option>
                            @foreach($prefectures as $pref)
                                <option value="{{ $pref }}" @selected(old('address1', $client->address1) === $pref)>{{ $pref }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="address2" class="form-label">市区町村 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('address2') is-invalid @enderror"
                               id="address2" name="address2" required maxlength="50"
                               value="{{ old('address2', $client->address2) }}">
                    </div>
                    <div class="mb-2">
                        <label for="address3" class="form-label">町名・番地 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('address3') is-invalid @enderror"
                               id="address3" name="address3" required maxlength="100"
                               value="{{ old('address3', $client->address3) }}">
                    </div>
                    <div class="mb-3">
                        <label for="address4" class="form-label">建物名・部屋番号</label>
                        <input type="text" class="form-control @error('address4') is-invalid @enderror"
                               id="address4" name="address4" maxlength="100"
                               value="{{ old('address4', $client->address4) }}">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">基本情報を保存</button>
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
