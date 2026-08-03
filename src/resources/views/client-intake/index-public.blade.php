@extends('layouts.client-intake')

@php
    // 他の client 側画面と同じ config キー(§9-1・§9-2)。新規キーは作らない
    $portalName = config('app.client_portal_name', 'トレーニング記録');
    $companyName = config('app.client_portal_company');
@endphp

{{-- 注: layouts.client-intake は <title> が「事前入力情報」で固定されており
     @section('title', ...) を受け取らない。この画面の browser tab title は
     「事前入力情報」となる制約がある(layouts は不可触) --}}

@section('content')
<div class="c-intake">
    <h1 class="c-intake-title">事前入力情報</h1>

    {{-- 3点シェブロン(設計書 §4-5): 基本情報 → 連絡先 → 完了。
         JS(showStep)は .step-badge 要素を data-step で識別し、
         .bg-primary / .bg-secondary を toggle する。
         この構造(class 名・data-step 属性)は変更禁止。
         3 つ目「完了」は .step-badge を付けないので JS は触らない --}}
    <div class="c-intake-progress" aria-label="入力の進み具合">
        <span class="badge bg-primary step-badge" data-step="1">基本情報</span>
        <span class="c-intake-progress-arrow" aria-hidden="true">→</span>
        <span class="badge bg-secondary step-badge" data-step="2">連絡先</span>
        <span class="c-intake-progress-arrow" aria-hidden="true">→</span>
        <span class="badge bg-secondary">完了</span>
    </div>

    {{-- プログレスバー: JS が #progressBar.style.width を書き込むため
         要素自体は残す。視覚的には隠す(3点シェブロンで代替表示) --}}
    <div class="progress" hidden>
        <div class="progress-bar" id="progressBar" role="progressbar" style="width: 50%" aria-hidden="true"></div>
    </div>

    <form method="POST" action="{{ route('client-intake.update-by-token', $token) }}" id="clientIntakeForm"
          onkeydown="if(event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') { event.preventDefault(); }">
        @csrf
        @method('PUT')

        {{-- サーバー側バリデーションエラー表示(chalk 面 = カード外だが、
             .alert-danger は自身が面を持つため §2-1 の siren on paper 制約は
             このアラート内部の text-on-alert-bg で守られる) --}}
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ステップ 1: 基本情報。id・class 名は変更禁止(showStep が参照) --}}
        <div class="card step-card" id="step1">
            <div class="card-body p-4">
                <h2 class="c-intake-step-title">基本情報（1/2）</h2>

                {{-- お名前(姓 + 名): 外側に 1 つのラベル、内部に 2 つの inputs。
                     入力ボックス個別のラベルは visually-hidden で a11y に提供 --}}
                <div class="mb-3">
                    <label class="form-label">
                        お名前 <span class="c-intake-required">（必須）</span>
                    </label>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="last_name" class="visually-hidden">姓</label>
                            <input type="text"
                                   class="form-control @error('last_name') is-invalid @enderror"
                                   id="last_name" name="last_name" inputmode="text"
                                   value="{{ old('last_name', $client->last_name) }}"
                                   placeholder="姓">
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label for="first_name" class="visually-hidden">名</label>
                            <input type="text"
                                   class="form-control"
                                   id="first_name" name="first_name" inputmode="text"
                                   value="{{ old('first_name', $client->first_name) }}"
                                   placeholder="名">
                        </div>
                    </div>
                </div>

                {{-- ふりがな(せい + めい) --}}
                <div class="mb-3">
                    <label class="form-label">ふりがな</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="last_name_kana" class="visually-hidden">せい</label>
                            <input type="text"
                                   class="form-control @error('last_name_kana') is-invalid @enderror"
                                   id="last_name_kana" name="last_name_kana" inputmode="hiragana"
                                   value="{{ old('last_name_kana', $client->last_name_kana) }}"
                                   placeholder="せい" autocomplete="off">
                            @error('last_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label for="first_name_kana" class="visually-hidden">めい</label>
                            <input type="text"
                                   class="form-control @error('first_name_kana') is-invalid @enderror"
                                   id="first_name_kana" name="first_name_kana" inputmode="hiragana"
                                   value="{{ old('first_name_kana', $client->first_name_kana) }}"
                                   placeholder="めい" autocomplete="off">
                            @error('first_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- 生年月日 + 性別 --}}
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="birth_date" class="form-label">生年月日</label>
                        {{-- .datepicker クラスは app.js の flatpickr 初期化が
                             参照するため変更禁止 --}}
                        <input type="text"
                               class="form-control datepicker"
                               id="birth_date" name="birth_date"
                               value="{{ old('birth_date', $client->birth_date?->format('Y-m-d')) }}"
                               placeholder="例: 2000-01-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10">
                    </div>
                    <div class="col-sm-6">
                        <label for="gender" class="form-label">性別</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value=""></option>
                            @foreach(['男', '女', '無回答'] as $g)
                                <option value="{{ $g }}" {{ old('gender', $client->gender) === $g ? 'selected' : '' }}>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="c-intake-actions">
                    <button type="button" class="btn btn-primary" onclick="showStep(2)">次へ</button>
                </div>
            </div>
        </div>

        {{-- ステップ 2: 連絡先。初期状態は .d-none。JS が toggle する --}}
        <div class="card step-card d-none" id="step2">
            <div class="card-body p-4">
                <h2 class="c-intake-step-title">連絡先（2/2）</h2>

                {{-- 電話番号 (1) + (2) --}}
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="phone1" class="form-label">電話番号（1）</label>
                        <input type="tel"
                               class="form-control @error('phone1') is-invalid @enderror"
                               id="phone1" name="phone1"
                               value="{{ old('phone1', $client->phone1) }}"
                               placeholder="例: 090-1234-5678">
                        @error('phone1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-6">
                        <label for="phone2" class="form-label">電話番号（2）</label>
                        <input type="tel"
                               class="form-control @error('phone2') is-invalid @enderror"
                               id="phone2" name="phone2"
                               value="{{ old('phone2', $client->phone2) }}"
                               placeholder="例: 03-1234-5678">
                        @error('phone2') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- 郵便番号 + 住所検索ボタン。has-validation は Bootstrap の
                     input-group 内での invalid-feedback レイアウト用 --}}
                <div class="mb-3">
                    <label for="postal_code" class="form-label">郵便番号</label>
                    <div class="input-group has-validation">
                        <input type="text"
                               class="form-control @error('postal_code') is-invalid @enderror"
                               id="postal_code" name="postal_code"
                               value="{{ old('postal_code', $client->postal_code) }}"
                               placeholder="例: 123-4567" inputmode="numeric" maxlength="8">
                        <button type="button" class="btn btn-outline-secondary" onclick="searchAddress()">住所を検索</button>
                        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    {{-- 住所検索のクライアントサイドエラー表示専用エリア。
                         input-group の外側に置き、.invalid-feedback の sibling
                         combinator 依存を避ける。alert() の代替(§6) --}}
                    <div id="postal_code_client_error" class="invalid-feedback d-block c-intake-inline-error" hidden></div>
                </div>

                {{-- 都道府県 + 市区町村 --}}
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="address1" class="form-label">都道府県</label>
                        <select class="form-select" id="address1" name="address1">
                            <option value=""></option>
                            @foreach(['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'] as $pref)
                                <option value="{{ $pref }}" {{ old('address1', $client->address1) == $pref ? 'selected' : '' }}>{{ $pref }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="address2" class="form-label">市区町村</label>
                        <input type="text" class="form-control"
                               id="address2" name="address2" inputmode="text"
                               value="{{ old('address2', $client->address2) }}">
                    </div>
                </div>

                {{-- 町名・番地 --}}
                <div class="mb-3">
                    <label for="address3" class="form-label">町名・番地</label>
                    <input type="text" class="form-control"
                           id="address3" name="address3" inputmode="text"
                           value="{{ old('address3', $client->address3) }}">
                </div>

                {{-- 建物名・部屋番号 --}}
                <div class="mb-3">
                    <label for="address4" class="form-label">建物名・部屋番号</label>
                    <input type="text" class="form-control"
                           id="address4" name="address4" inputmode="text"
                           value="{{ old('address4', $client->address4) }}">
                </div>

                <div class="c-intake-actions c-intake-actions--split">
                    <button type="button" class="btn btn-outline-secondary" onclick="showStep(1)">戻る</button>
                    <button type="submit" class="btn btn-primary">送信する</button>
                </div>
            </div>
        </div>
    </form>

    @if($companyName)
        <p class="c-intake-footer">&copy; {{ date('Y') }} {{ $companyName }}</p>
    @endif
</div>

<script>
    // ステップ切替(既存ロジックを維持。id / class 依存部分は変更禁止)
    function showStep(step) {
        var currentStep = document.querySelector('.step-card:not(.d-none)');
        var currentStepNum = currentStep ? parseInt(currentStep.id.replace('step', '')) : 1;

        // 前進時のみステップ 1 の必須チェック
        if (currentStepNum === 1 && step > 1) {
            var requiredFields = currentStep.querySelectorAll('[required]');
            var valid = true;
            requiredFields.forEach(function(field) {
                if (!field.value) {
                    field.classList.add('is-invalid');
                    valid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            // 姓の必須チェック(サーバー側で last_name のみ required)
            clearNameError();
            if (!document.getElementById('last_name').value.trim()) {
                showNameError('last_name', 'お名前(姓)を入力してください。');
                valid = false;
            }

            // ひらがなバリデーション
            var hiraganaRegex = /^[ぁ-んー\s　]*$/;
            var kanaFields = [
                { id: 'last_name_kana', label: 'せい' },
                { id: 'first_name_kana', label: 'めい' },
            ];
            kanaFields.forEach(function(f) {
                clearFieldError(f.id);
                var val = document.getElementById(f.id).value.trim();
                if (val !== '' && !hiraganaRegex.test(val)) {
                    showFieldError(f.id, f.label + 'はひらがなで入力してください。');
                    valid = false;
                }
            });

            if (!valid) return;
        }

        // 全ステップを非表示
        document.querySelectorAll('.step-card').forEach(function(card) { card.classList.add('d-none'); });
        // 対象ステップを表示
        document.getElementById('step' + step).classList.remove('d-none');

        // プログレスバー更新(要素は hidden 属性で非表示だが JS の style 書き込みは有効)
        document.getElementById('progressBar').style.width = (step * 100 / 2) + '%';

        // バッジ更新(.step-badge の bg-primary / bg-secondary を toggle)
        document.querySelectorAll('.step-badge').forEach(function(badge) {
            var badgeStep = parseInt(badge.getAttribute('data-step'));
            if (badgeStep <= step) {
                badge.classList.remove('bg-secondary');
                badge.classList.add('bg-primary');
            } else {
                badge.classList.remove('bg-primary');
                badge.classList.add('bg-secondary');
            }
        });

        // ページ先頭にスクロール
        window.scrollTo(0, 0);
    }

    // エラー表示ヘルパー(姓 / kana のインライン検証で使用)
    function showNameError(fieldId, message) {
        var field = document.getElementById(fieldId);
        field.classList.add('is-invalid');
        var div = document.createElement('div');
        div.className = 'invalid-feedback';
        div.textContent = message;
        div.id = fieldId + '_error';
        field.parentNode.appendChild(div);
    }

    function clearNameError() {
        ['last_name'].forEach(function(id) {
            var field = document.getElementById(id);
            field.classList.remove('is-invalid');
            var err = document.getElementById(id + '_error');
            if (err) err.remove();
        });
    }

    function showFieldError(fieldId, message) {
        var field = document.getElementById(fieldId);
        field.classList.add('is-invalid');
        var div = document.createElement('div');
        div.className = 'invalid-feedback';
        div.textContent = message;
        div.id = fieldId + '_error';
        field.parentNode.appendChild(div);
    }

    function clearFieldError(fieldId) {
        var field = document.getElementById(fieldId);
        field.classList.remove('is-invalid');
        var err = document.getElementById(fieldId + '_error');
        if (err) err.remove();
    }

    // 住所検索(zipcloud API)
    // 設計書 §6 に従い alert() を廃止し、専用エラーエリア
    // (#postal_code_client_error)に人の言葉でインライン表示する。
    // showFieldError()を使わない理由: postal_code は input-group 内にあり、
    // parentNode(input-group) 内に .invalid-feedback を挿入しても sibling
    // combinator が button 越しに正しく効かない場合がある。専用の d-block
    // エリアを HTML に用意することで確実に表示させる
    function searchAddress() {
        var errorEl = document.getElementById('postal_code_client_error');
        var postalEl = document.getElementById('postal_code');

        // 既存エラー表示をクリア
        errorEl.hidden = true;
        errorEl.textContent = '';
        postalEl.classList.remove('is-invalid');

        var postalCode = postalEl.value.replace(/[^0-9]/g, '');
        if (postalCode.length !== 7) {
            postalEl.classList.add('is-invalid');
            errorEl.textContent = '郵便番号は 7 桁の数字で入力してください。';
            errorEl.hidden = false;
            return;
        }

        fetch('https://zipcloud.ibsnet.co.jp/api/search?zipcode=' + postalCode)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.results) {
                    var result = data.results[0];
                    document.getElementById('address1').value = result.address1;
                    document.getElementById('address2').value = result.address2;
                    document.getElementById('address3').value = result.address3;
                } else {
                    postalEl.classList.add('is-invalid');
                    errorEl.textContent = 'この郵便番号に一致する住所が見つかりませんでした。手入力してください。';
                    errorEl.hidden = false;
                }
            })
            .catch(function() {
                postalEl.classList.add('is-invalid');
                errorEl.textContent = '住所を取得できませんでした。時間をおいて試すか、手入力してください。';
                errorEl.hidden = false;
            });
    }
</script>
@endsection
