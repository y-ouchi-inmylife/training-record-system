@extends('layouts.app')

@section('title', 'クライアント登録')

@section('content')
<div class="container">
    <h2 class="mb-4">クライアント登録</h2>

    {{-- プログレスバー --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between mb-2">
            <span class="badge bg-primary step-badge" data-step="1">1. 基本情報</span>
            <span class="badge bg-secondary step-badge" data-step="2">2. 支援管理</span>
            <span class="badge bg-secondary step-badge" data-step="3">3. 連絡先</span>
        </div>
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 16.7%" id="progressBar"></div>
        </div>
    </div>

    <form method="POST" action="{{ route('clients.store') }}" id="clientForm"
          onkeydown="if(event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') { event.preventDefault(); }"
          onsubmit="return validateBeforeSubmit()">
        @csrf

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
                <h5 class="mb-0">ステップ 1/3: 基本情報</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="initial_consultation_date" class="form-label">初回日 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control datepicker @error('initial_consultation_date') is-invalid @enderror"
                               id="initial_consultation_date" name="initial_consultation_date"
                               value="{{ old('initial_consultation_date', date('Y-m-d')) }}" required
                               placeholder="例: 2000-01-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10">
                        @error('initial_consultation_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="w-100 mt-0"></div>

                    <div class="col-md-3">
                        <label for="last_name" class="form-label">姓 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                               id="last_name" name="last_name" inputmode="text" value="{{ old('last_name') }}">
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="first_name" class="form-label">名</label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                               id="first_name" name="first_name" inputmode="text" value="{{ old('first_name') }}">
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="last_name_kana" class="form-label">せい</label>
                        <input type="text" class="form-control @error('last_name_kana') is-invalid @enderror"
                               id="last_name_kana" name="last_name_kana" inputmode="hiragana" value="{{ old('last_name_kana') }}"
                               placeholder="例: やまだ">
                        @error('last_name_kana')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="first_name_kana" class="form-label">めい</label>
                        <input type="text" class="form-control @error('first_name_kana') is-invalid @enderror"
                               id="first_name_kana" name="first_name_kana" inputmode="hiragana" value="{{ old('first_name_kana') }}"
                               placeholder="例: たろう">
                        @error('first_name_kana')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="birth_date" class="form-label">生年月日</label>
                        <input type="text" class="form-control datepicker" id="birth_date" name="birth_date"
                               value="{{ old('birth_date') }}"
                               placeholder="例: 2000-01-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10">
                    </div>
                    <div class="col-md-3">
                        <label for="gender" class="form-label">性別</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="">選択してください</option>
                            @foreach(['男', '女', 'その他'] as $g)
                                <option value="{{ $g }}" {{ old('gender') === $g ? 'selected' : '' }}>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('clients.index') }}" class="btn btn-secondary js-leave-link">キャンセル</a>
                <button type="button" class="btn btn-primary" onclick="showStep(2)">次へ</button>
            </div>
        </div>

        {{-- ステップ2: 支援管理 --}}
        <div class="card step-card d-none" id="step2">
            <div class="card-header">
                <h5 class="mb-0">ステップ 2/3: 支援管理</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="primary_trainer_id" class="form-label">主担当</label>
                        <select class="form-select" id="primary_trainer_id" name="primary_trainer_id">
                            <option value="">選択してください</option>
                            @foreach($trainers as $trainer)
                                <option value="{{ $trainer->id }}" {{ old('primary_trainer_id') == $trainer->id ? 'selected' : '' }}>
                                    {{ $trainer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="support_status_id" class="form-label">支援状態</label>
                        <select class="form-select" id="support_status_id" name="support_status_id">
                            <option value="">選択してください</option>
                            @foreach($supportStatuses as $status)
                                <option value="{{ $status->id }}" {{ old('support_status_id') == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" onclick="showStep(1)">戻る</button>
                <button type="button" class="btn btn-primary" onclick="showStep(3)">次へ</button>
            </div>
        </div>

        {{-- ステップ3: 連絡先 --}}
        <div class="card step-card d-none" id="step3">
            <div class="card-header">
                <h5 class="mb-0">ステップ 3/3: 連絡先</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- 行1: 電話番号 --}}
                    <div class="col-md-3">
                        <label for="phone1" class="form-label">電話番号1</label>
                        <input type="tel" class="form-control @error('phone1') is-invalid @enderror"
                               id="phone1" name="phone1" value="{{ old('phone1') }}" placeholder="例: 090-1234-5678">
                        @error('phone1')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="phone2" class="form-label">電話番号2</label>
                        <input type="tel" class="form-control @error('phone2') is-invalid @enderror"
                               id="phone2" name="phone2" value="{{ old('phone2') }}" placeholder="例: 090-1234-5678">
                        @error('phone2')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="phone3" class="form-label">電話番号3（緊急連絡先）</label>
                        <input type="tel" class="form-control @error('phone3') is-invalid @enderror"
                               id="phone3" name="phone3" value="{{ old('phone3') }}" placeholder="例: 090-1234-5678">
                        @error('phone3')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- 行2: 郵便番号 + 住所検索 + 都道府県 + 市区町村 --}}
                    <div class="col-md-2">
                        <label for="postal_code" class="form-label">郵便番号</label>
                        <input type="text" class="form-control @error('postal_code') is-invalid @enderror"
                               id="postal_code" name="postal_code" value="{{ old('postal_code') }}"
                               placeholder="例: 123-4567">
                        @error('postal_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btn-search-address" onclick="searchAddress()">住所検索</button>
                    </div>
                    <div class="col-md-3">
                        <label for="address1" class="form-label">都道府県</label>
                        <select class="form-select" id="address1" name="address1">
                            <option value="">選択してください</option>
                            @foreach(['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'] as $pref)
                                <option value="{{ $pref }}" {{ old('address1') == $pref ? 'selected' : '' }}>{{ $pref }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="address2" class="form-label">市区町村</label>
                        <input type="text" class="form-control" id="address2" name="address2"
                               inputmode="text" value="{{ old('address2') }}">
                    </div>
                    {{-- 行3: 町名・番地 + 建物名 + 最寄り駅 --}}
                    <div class="col-md-4">
                        <label for="address3" class="form-label">町名・番地</label>
                        <input type="text" class="form-control" id="address3" name="address3"
                               inputmode="text" value="{{ old('address3') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="address4" class="form-label">建物名・部屋番号</label>
                        <input type="text" class="form-control" id="address4" name="address4"
                               inputmode="text" value="{{ old('address4') }}">
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" onclick="showStep(2)">戻る</button>
                <button type="submit" class="btn btn-success">登録</button>
            </div>
        </div>

    </form>
</div>

<script>
    // ステップ切替
    function showStep(step) {
        // 現在のステップを取得
        const currentStep = document.querySelector('.step-card:not(.d-none)');
        const currentStepNum = currentStep ? parseInt(currentStep.id.replace('step', '')) : 1;

        // 前進時のみステップ1の必須チェック
        if (currentStepNum === 1 && step > 1) {
            const requiredFields = currentStep.querySelectorAll('[required]');
            let valid = true;
            requiredFields.forEach(field => {
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
            const hiraganaRegex = /^[\u3041-\u3093\u30FC\s\u3000]*$/;
            const kanaFields = [
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

            if (!valid) {
                return;
            }
        }

        // 全ステップを非表示
        document.querySelectorAll('.step-card').forEach(card => card.classList.add('d-none'));
        // 対象ステップを表示
        document.getElementById('step' + step).classList.remove('d-none');

        // プログレスバー更新
        document.getElementById('progressBar').style.width = (step * 100 / 3) + '%';

        // バッジ更新
        document.querySelectorAll('.step-badge').forEach(badge => {
            const badgeStep = parseInt(badge.dataset.step);
            badge.classList.remove('bg-primary', 'bg-secondary');
            badge.classList.add(badgeStep <= step ? 'bg-primary' : 'bg-secondary');
        });

        // 画面上部へスクロール
        window.scrollTo(0, 0);
    }

    // フィールドエラー表示
    function showFieldError(fieldId, message) {
        var field = document.getElementById(fieldId);
        field.classList.add('is-invalid');
        var errorDiv = field.parentElement.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentElement.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }

    function clearFieldError(fieldId) {
        var field = document.getElementById(fieldId);
        field.classList.remove('is-invalid');
        var errorDiv = field.parentElement.querySelector('.invalid-feedback');
        if (errorDiv) errorDiv.style.display = 'none';
    }

    // 後方互換（既存コードが使っている場合に備える）
    function showNameError(fieldId, message) { showFieldError(fieldId, message); }
    function clearNameError() {
        ['last_name'].forEach(clearFieldError);
    }

    // フォーム送信前のバリデーション（最終ステップで実行）
    function validateBeforeSubmit() {
        let valid = true;
        clearFieldError('email');

        // メールアドレスの形式チェック
        const email = document.getElementById('email').value.trim();
        if (email !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showFieldError('email', 'メールアドレスの形式が正しくありません。');
                valid = false;
            }
        }

        return valid;
    }

    function searchAddress() {
        const postalCode = document.getElementById('postal_code').value.replace(/[^0-9]/g, '');
        if (postalCode.length !== 7) {
            alert('郵便番号を7桁で入力してください。');
            return;
        }

        const btn = document.getElementById('btn-search-address');
        btn.disabled = true;
        btn.textContent = '検索中…';

        fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${postalCode}`)
            .then(response => response.json())
            .then(data => {
                if (data.results) {
                    const result = data.results[0];
                    document.getElementById('address1').value = result.address1;
                    document.getElementById('address2').value = result.address2;
                    document.getElementById('address3').value = result.address3;
                } else {
                    alert('該当する住所が見つかりませんでした。');
                }
            })
            .catch(() => {
                alert('住所検索に失敗しました。');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '住所検索';
            });
    }
</script>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr('.datepicker', {
            locale: 'ja',
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
        });

        // 未保存変更警告
        new window.UnsavedChangesGuard({
            formSelector: '#clientForm',
            leaveLinkSelector: '.js-leave-link'
        }).init();
    });
</script>
@endpush
