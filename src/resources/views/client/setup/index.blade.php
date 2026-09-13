@extends('layouts.client-public')

@php
    $portalName = config('app.client_portal_name', 'トレーニング記録');
    $companyName = config('app.client_portal_company');
    $prefectures = config('prefectures');
@endphp

@section('title', '初回設定')

@section('content')
{{-- pre-auth シェル（ログイン画面と共用）+ 幅広モディファイア。
     初回設定はパスワード＋氏名＋連絡先＋住所を 1 画面に載せるため
     .c-login--wide で md 以上のカード幅を拡張する（設計書 §4-7）。
     ワードマークは layouts.partials.client-nav（ヘッダー帯の左）に移動。
     画面構成は S-1406 / S-1411 と揃える：見出しはカード外、セクション
     見出し（.eyebrow の ── XXX ── 装飾）は置かず、代わりに先頭に
     メールアドレスの表示行を置く（S-1411「お名前」と同じマークアップ）。 --}}
<div class="c-login c-login--wide">
    <h1 class="mb-4">初回設定</h1>

    <div class="card c-login-card">
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach ($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- メールアドレスは表示のみ（S-1411 の「お名前」表示行と同じマークアップに揃える）。
                 説明文で埋め込むより行として立っている方が、ログイン ID の確認場面として目に入る。
                 下に区切り線は引かない：ここは入力欄ではなく「読む部分と書く部分の切り替わり」
                 で、行の見た目（小さいラベルと枠のない値）が下のパスワード入力欄と違うため、
                 線がなくても区切られて見える。区切り線は「入力欄のまとまりの境目に引く」
                 定義（設計書 S-1403 備考 / client-portal-design-plan.md §4-7 参照）。 --}}
            <div class="mb-3">
                <div class="text-muted small">メールアドレス</div>
                <div>{{ $client->email }}</div>
            </div>

            <form method="POST" action="{{ route('client-portal.setup.store', ['token' => $token]) }}">
                @csrf

                {{-- パスワードマネージャー向け username（保存パスワードの紐付け先を明示）--}}
                <input
                    type="email"
                    name="username"
                    value="{{ $client->email }}"
                    autocomplete="username"
                    readonly
                    tabindex="-1"
                    aria-hidden="true"
                    class="visually-hidden"
                >

                <div class="mb-3">
                    <label for="password" class="form-label">パスワード <span class="text-danger">*</span></label>
                    <input
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        required
                        autofocus
                        autocomplete="new-password"
                        aria-describedby="password-help"
                    >
                    <div id="password-help" class="form-text">
                        8 文字以上で、大文字・小文字・数字・記号をそれぞれ 1 つ以上入れてください。
                    </div>
                </div>
                {{-- パスワード(確認) の下にまとまりの区切り線を引く(4 まとまりの境目の 2 本目)。
                     pb-3 は罫線とパスワード確認欄下端の間隔、mb-3 は罫線と次のまとまり(氏名)との間隔。 --}}
                <div class="mb-3 pb-3 border-bottom">
                    <label for="password_confirmation" class="form-label">パスワード（確認） <span class="text-danger">*</span></label>
                    <input
                        type="password"
                        class="form-control"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    >
                </div>

                {{-- 姓・名、せい・めいは横二列のまま維持（縦一列標準への意図的な例外）。
                     姓名は対で入力するもので横に並ぶのが自然。スマホでも 2 項目なら収まる。
                     設計書 client-portal-design-plan.md §4-7 参照。 --}}
                <div class="row g-2 mb-2">
                    <div class="col-sm-6">
                        <label for="last_name" class="form-label">姓 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                               id="last_name" name="last_name" required maxlength="50"
                               value="{{ old('last_name', $client->last_name) }}">
                    </div>
                    <div class="col-sm-6">
                        <label for="first_name" class="form-label">名</label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                               id="first_name" name="first_name" maxlength="50"
                               value="{{ old('first_name', $client->first_name) }}">
                    </div>
                </div>
                {{-- せい・めい行の下にまとまりの区切り線を引く（4 まとまりの境目の 3 本目、氏名まとまりの下）。
                     連絡先まとまりの下（最終まとまり）には線を引かない（カード枠が外周を担当するため。
                     設計書 S-1403 備考 / client-portal-design-plan.md §4-7 参照）。 --}}
                <div class="row g-2 mb-3 pb-3 border-bottom">
                    <div class="col-sm-6">
                        <label for="last_name_kana" class="form-label">せい</label>
                        <input type="text" class="form-control @error('last_name_kana') is-invalid @enderror"
                               id="last_name_kana" name="last_name_kana" maxlength="50"
                               value="{{ old('last_name_kana', $client->last_name_kana) }}">
                    </div>
                    <div class="col-sm-6">
                        <label for="first_name_kana" class="form-label">めい</label>
                        <input type="text" class="form-control @error('first_name_kana') is-invalid @enderror"
                               id="first_name_kana" name="first_name_kana" maxlength="50"
                               value="{{ old('first_name_kana', $client->first_name_kana) }}">
                    </div>
                </div>

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

                @include('layouts.partials.privacy-consent')

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">設定して進む</button>
                </div>
            </form>
        </div>
    </div>

    @if($companyName)
        <p class="c-login-footer">&copy; {{ date('Y') }} {{ $companyName }}</p>
    @endif
</div>

{{-- 住所検索スクリプト（郵便番号 → 住所自動入力）--}}
@vite(['resources/js/address-search.js'])
@endsection
