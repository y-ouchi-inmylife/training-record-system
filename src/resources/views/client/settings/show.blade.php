@extends('layouts.client')

@php
    // 住所を 2 行に組み立てる。
    // 1 行目：〒郵便番号 + 都道府県 + 市区町村 + 町名・番地（各要素は入力があれば繋げる）
    // 2 行目：建物名・部屋番号（入力があるときのみ）
    // §2-4 に従い、未入力の要素は詰めて、空白や記号を残さない。
    $addressLine1Parts = array_filter([
        $client->postal_code ? '〒' . $client->postal_code : null,
        $client->address1,
        $client->address2,
        $client->address3,
    ], fn ($v) => $v !== null && $v !== '');
    $addressLine1 = implode(' ', $addressLine1Parts);
    $addressLine2 = $client->address4;
    $hasAddress = $addressLine1 !== '' || $addressLine2;
@endphp

@section('title', '登録情報')

@section('content')
<div class="container">
    <div class="c-settings">
        <h1 class="mb-4">登録情報</h1>

        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <x-detail-cell label="お名前" :value="$client->full_name" />
                    <x-detail-cell label="お名前（かな）" :value="$client->full_name_kana" />

                    {{-- 住所は複数カラムを組み合わせるためスロットで自前描画。§2-4 に従い
                         未入力のときは何も表示しない（ラベルのみ残る） --}}
                    <x-detail-cell label="住所">
                        @if($hasAddress)
                            @if($addressLine1 !== ''){{ $addressLine1 }}@endif
                            @if($addressLine2)<br>{{ $addressLine2 }}@endif
                        @endif
                    </x-detail-cell>

                    <x-detail-cell label="電話番号" :value="$client->phone1" />
                    <x-detail-cell label="電話番号（予備）" :value="$client->phone2" />
                    <x-detail-cell label="メールアドレス" :value="$client->email" />
                </div>
            </div>
        </div>

        {{-- 3 つの入口ボタンは同格。フル幅の .btn-primary（明るいオレンジ #EC6812）で揃える。
             並び順：登録情報 → パスワード → メールアドレス（設計書 §4-10 参照）--}}
        <div class="d-grid gap-2 mb-4">
            <a href="{{ route('client-portal.profile.edit') }}" class="btn btn-primary">登録情報を変更</a>
            <a href="{{ route('client-portal.settings.password.edit') }}" class="btn btn-primary">パスワードを変更</a>
            <a href="{{ route('client-portal.settings.email.edit') }}" class="btn btn-primary">メールアドレスを変更</a>
        </div>

        {{-- 画面下部の案内（設計書 §4-10）--}}
        <p class="text-muted small text-center mb-0">
            利用をやめたい場合は担当トレーナーにご連絡ください
        </p>
    </div>
</div>
@endsection
