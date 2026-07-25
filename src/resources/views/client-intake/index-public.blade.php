@extends('layouts.client-intake')

@section('content')
<h2 class="mb-4">情報入力</h2>

{{-- プログレスバー --}}
<div class="mb-4">
    <div class="d-flex justify-content-between mb-2">
        <span class="badge bg-primary step-badge" data-step="1">1. 基本情報</span>
        <span class="badge bg-secondary step-badge" data-step="2">2. 連絡先</span>
    </div>
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: 50%" id="progressBar"></div>
    </div>
</div>

<form method="POST" action="{{ route('client-intake.update-by-token', $token) }}" id="clientIntakeForm"
      onkeydown="if(event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') { event.preventDefault(); }">
    @csrf
    @method('PUT')

    {{-- バリデーションエラー表示 --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ステップ1: 基本情報 --}}
    <div class="card step-card" id="step1">
        <div class="card-header">
            <h5 class="mb-0">ステップ 1/2: 基本情報</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-2">
                {{-- 行1: 名前(姓+名) + ふりがな(せい+めい) --}}
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <label for="last_name" class="col-md-auto col-form-label text-md-end form-label-fixed">
                            名前 <span class="text-danger">*</span>
                        </label>
                        <div class="col-12 col-md">
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                           id="last_name" name="last_name" inputmode="text"
                                           value="{{ old('last_name', $client->last_name) }}" placeholder="姓">
                                    @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                           inputmode="text" value="{{ old('first_name', $client->first_name) }}" placeholder="名">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <label for="last_name_kana" class="col-md-auto col-form-label text-md-end form-label-fixed">ふりがな</label>
                        <div class="col-12 col-md">
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" class="form-control @error('last_name_kana') is-invalid @enderror"
                                           id="last_name_kana" name="last_name_kana" inputmode="hiragana"
                                           value="{{ old('last_name_kana', $client->last_name_kana) }}" placeholder="せい">
                                    @error('last_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control @error('first_name_kana') is-invalid @enderror"
                                           id="first_name_kana" name="first_name_kana" inputmode="hiragana"
                                           value="{{ old('first_name_kana', $client->first_name_kana) }}" placeholder="めい">
                                    @error('first_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                {{-- 行2: 生年月日 + 性別 --}}
                <div class="col-md-3">
                    <div class="row g-2 align-items-center">
                        <label for="birth_date" class="col-md-auto col-form-label text-md-end form-label-fixed">生年月日</label>
                        <div class="col-12 col-md">
                            <input type="text" class="form-control datepicker" id="birth_date" name="birth_date"
                                   value="{{ old('birth_date', $client->birth_date?->format('Y-m-d')) }}"
                                   placeholder="例: 2000-01-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="row g-2 align-items-center">
                        <label for="gender" class="col-md-auto col-form-label text-md-end form-label-fixed">性別</label>
                        <div class="col-12 col-md">
                            <select class="form-select" id="gender" name="gender">
                                <option value=""></option>
                                @foreach(['男', '女', 'その他'] as $g)
                                    <option value="{{ $g }}" {{ old('gender', $client->gender) === $g ? 'selected' : '' }}>{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="button" class="btn btn-primary" onclick="showStep(2)">次へ</button>
        </div>
    </div>

    {{-- ステップ2: 連絡先 --}}
    <div class="card step-card d-none" id="step2">
        <div class="card-header">
            <h5 class="mb-0">ステップ 2/2: 連絡先</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-2">
                {{-- 行1: 電話番号1 + 電話番号2 --}}
                <div class="col-md-3">
                    <div class="row g-2 align-items-center">
                        <label for="phone1" class="col-md-auto col-form-label text-md-end form-label-fixed">電話番号1</label>
                        <div class="col-12 col-md">
                            <input type="tel" class="form-control @error('phone1') is-invalid @enderror"
                                   id="phone1" name="phone1" value="{{ old('phone1', $client->phone1) }}" placeholder="例: 090-1234-5678">
                            @error('phone1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="row g-2 align-items-center">
                        <label for="phone2" class="col-md-auto col-form-label text-md-end form-label-fixed">電話番号2</label>
                        <div class="col-12 col-md">
                            <input type="tel" class="form-control @error('phone2') is-invalid @enderror"
                                   id="phone2" name="phone2" value="{{ old('phone2', $client->phone2) }}" placeholder="例: 090-1234-5678">
                            @error('phone2') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-2">
                {{-- 行2: 郵便番号+住所検索 + 都道府県 + 市区町村 --}}
                <div class="col-md-3">
                    <div class="row g-2 align-items-center">
                        <label for="postal_code" class="col-md-auto col-form-label text-md-end form-label-fixed">郵便番号</label>
                        <div class="col-12 col-md">
                            <div class="input-group">
                                <input type="text" class="form-control @error('postal_code') is-invalid @enderror"
                                       id="postal_code" name="postal_code" value="{{ old('postal_code', $client->postal_code) }}" placeholder="例: 123-4567">
                                <button type="button" class="btn btn-outline-secondary" onclick="searchAddress()">住所検索</button>
                                @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="row g-2 align-items-center">
                        <label for="address1" class="col-md-auto col-form-label text-md-end form-label-fixed">都道府県</label>
                        <div class="col-12 col-md">
                            <select class="form-select" id="address1" name="address1">
                                <option value=""></option>
                                @foreach(['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'] as $pref)
                                    <option value="{{ $pref }}" {{ old('address1', $client->address1) == $pref ? 'selected' : '' }}>{{ $pref }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="row g-2 align-items-center">
                        <label for="address2" class="col-md-auto col-form-label text-md-end form-label-fixed">市区町村</label>
                        <div class="col-12 col-md">
                            <input type="text" class="form-control" id="address2" name="address2" inputmode="text" value="{{ old('address2', $client->address2) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                {{-- 行3: 町名・番地 + 建物名・部屋番号 --}}
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <label for="address3" class="col-md-auto col-form-label text-md-end form-label-fixed">町名・番地</label>
                        <div class="col-12 col-md">
                            <input type="text" class="form-control" id="address3" name="address3" inputmode="text" value="{{ old('address3', $client->address3) }}">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <label for="address4" class="col-md-auto col-form-label text-md-end form-label-fixed">建物名・部屋番号</label>
                        <div class="col-12 col-md">
                            <input type="text" class="form-control" id="address4" name="address4" inputmode="text" value="{{ old('address4', $client->address4) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" onclick="showStep(1)">戻る</button>
            <button type="submit" class="btn btn-success">送信</button>
        </div>
    </div>

</form>

<script>
    // ステップ切替
    function showStep(step) {
        var currentStep = document.querySelector('.step-card:not(.d-none)');
        var currentStepNum = currentStep ? parseInt(currentStep.id.replace('step', '')) : 1;

        // 前進時のみステップ1の必須チェック
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

            // 姓の必須チェック
            clearNameError();
            if (!document.getElementById('last_name').value.trim()) {
                showNameError('last_name', '姓は必須です。');
                valid = false;
            }

            // ひらがなバリデーション
            var hiraganaRegex = /^[\u3041-\u3093\u30FC\s\u3000]*$/;
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

        // プログレスバー更新
        document.getElementById('progressBar').style.width = (step * 100 / 2) + '%';

        // バッジ更新
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

    // エラー表示ヘルパー
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

    // 住所検索（zipcloud API）
    function searchAddress() {
        var postalCode = document.getElementById('postal_code').value.replace(/[^0-9]/g, '');
        if (postalCode.length !== 7) {
            alert('郵便番号を正しく入力してください（7桁）');
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
                    alert('住所が見つかりませんでした');
                }
            })
            .catch(function() {
                alert('住所検索に失敗しました');
            });
    }
</script>
@endsection
