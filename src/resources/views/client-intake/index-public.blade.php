@extends('layouts.client-intake')

@section('content')
<h2 class="mb-4" style="font-size: 2rem;">新規登録</h2>

{{-- プログレスバー --}}
<div class="mb-4">
    <div class="d-flex justify-content-between mb-2 step-badges">
        <span class="badge bg-primary step-badge" data-step="1">1. 基本情報</span>
        <span class="badge bg-secondary step-badge" data-step="2">2. 連絡先</span>
        <span class="badge bg-secondary step-badge" data-step="3">3. 学歴・職歴</span>
        <span class="badge bg-secondary step-badge" data-step="4">4. 障害・医療情報</span>
        <span class="badge bg-secondary step-badge" data-step="5">5. 生活状況</span>
    </div>
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: 20%" id="progressBar"></div>
    </div>
</div>

<form method="POST" action="{{ route('client-intake.store-by-token', $token) }}" id="clientIntakeForm"
      onkeydown="if(event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') { event.preventDefault(); }">
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
            <h5 class="mb-0">ステップ 1/5: 基本情報</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="hidden" name="initial_consultation_date" value="{{ $tokenRecord->initial_consultation_date->format('Y-m-d') }}">
                </div>

                <div class="w-100"></div>

                <div class="col-md-3">
                    <label for="last_name" class="form-label">姓（本人）</label>
                    <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                           id="last_name" name="last_name" inputmode="text" value="{{ old('last_name') }}">
                    @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="first_name" class="form-label">名（本人）</label>
                    <input type="text" class="form-control" id="first_name" name="first_name"
                           inputmode="text" value="{{ old('first_name') }}">
                </div>
                <div class="col-md-3">
                    <label for="last_name_kana" class="form-label">せい（本人）</label>
                    <input type="text" class="form-control @error('last_name_kana') is-invalid @enderror"
                           id="last_name_kana" name="last_name_kana" inputmode="hiragana"
                           value="{{ old('last_name_kana') }}" placeholder="例: やまだ">
                    @error('last_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="first_name_kana" class="form-label">めい（本人）</label>
                    <input type="text" class="form-control @error('first_name_kana') is-invalid @enderror"
                           id="first_name_kana" name="first_name_kana" inputmode="hiragana"
                           value="{{ old('first_name_kana') }}" placeholder="例: たろう">
                    @error('first_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label for="family_last_name" class="form-label">姓（家族など）</label>
                    <input type="text" class="form-control @error('family_last_name') is-invalid @enderror"
                           id="family_last_name" name="family_last_name" inputmode="text" value="{{ old('family_last_name') }}">
                    @error('family_last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="family_first_name" class="form-label">名（家族など）</label>
                    <input type="text" class="form-control" id="family_first_name" name="family_first_name"
                           inputmode="text" value="{{ old('family_first_name') }}">
                </div>
                <div class="col-md-3">
                    <label for="family_last_name_kana" class="form-label">せい（家族など）</label>
                    <input type="text" class="form-control @error('family_last_name_kana') is-invalid @enderror"
                           id="family_last_name_kana" name="family_last_name_kana"
                           inputmode="hiragana" value="{{ old('family_last_name_kana') }}" placeholder="例: さとう">
                    @error('family_last_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="family_first_name_kana" class="form-label">めい（家族など）</label>
                    <input type="text" class="form-control @error('family_first_name_kana') is-invalid @enderror"
                           id="family_first_name_kana" name="family_first_name_kana"
                           inputmode="hiragana" value="{{ old('family_first_name_kana') }}" placeholder="例: はなこ">
                    @error('family_first_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="family_relationship" class="form-label">本人との関係 <span class="text-danger">*</span></label>
                    <select class="form-select @error('family_relationship') is-invalid @enderror"
                            id="family_relationship" name="family_relationship" required>
                        <option value="">選択してください</option>
                        @foreach(['本人', '母', '父', '配偶者', 'きょうだい', '子', '祖父母', 'その他'] as $rel)
                            <option value="{{ $rel }}" {{ old('family_relationship') === $rel ? 'selected' : '' }}>{{ $rel }}</option>
                        @endforeach
                    </select>
                    @error('family_relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="family_relationship_detail" class="form-label">関係の詳細</label>
                    <input type="text" class="form-control" id="family_relationship_detail"
                           name="family_relationship_detail" inputmode="text" value="{{ old('family_relationship_detail') }}">
                </div>

                <div class="w-100"></div>

                <div class="col-md-3">
                    <label for="birth_date" class="form-label">生年月日（本人）</label>
                    <input type="text" class="form-control datepicker" id="birth_date" name="birth_date" value="{{ old('birth_date') }}"
                           placeholder="例: 2000-01-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10">
                </div>
                <div class="col-md-2">
                    <label for="initial_age" class="form-label">初回時年齢（本人）</label>
                    <input type="number" class="form-control @error('initial_age') is-invalid @enderror"
                           id="initial_age" name="initial_age" value="{{ old('initial_age') }}" min="0" max="150">
                    @error('initial_age') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="gender" class="form-label">性別（本人）</label>
                    <select class="form-select" id="gender" name="gender">
                        <option value="">選択してください</option>
                        @foreach(['男', '女', 'その他'] as $g)
                            <option value="{{ $g }}" {{ old('gender') === $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="button" class="btn btn-primary btn-lg" onclick="showStep(2)">次へ</button>
        </div>
    </div>

    {{-- ステップ2: 連絡先 --}}
    <div class="card step-card d-none" id="step2">
        <div class="card-header">
            <h5 class="mb-0">ステップ 2/5: 連絡先</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="phone1" class="form-label">電話番号1</label>
                    <input type="tel" class="form-control @error('phone1') is-invalid @enderror"
                           id="phone1" name="phone1" value="{{ old('phone1') }}" placeholder="例: 090-1234-5678">
                    @error('phone1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="phone2" class="form-label">電話番号2</label>
                    <input type="tel" class="form-control @error('phone2') is-invalid @enderror"
                           id="phone2" name="phone2" value="{{ old('phone2') }}" placeholder="例: 090-1234-5678">
                    @error('phone2') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="phone3" class="form-label">電話番号3（緊急連絡先）</label>
                    <input type="tel" class="form-control @error('phone3') is-invalid @enderror"
                           id="phone3" name="phone3" value="{{ old('phone3') }}" placeholder="例: 090-1234-5678">
                    @error('phone3') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                           id="email" name="email" value="{{ old('email') }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-2">
                    <label for="postal_code" class="form-label">郵便番号</label>
                    <input type="text" class="form-control @error('postal_code') is-invalid @enderror"
                           id="postal_code" name="postal_code" value="{{ old('postal_code') }}" placeholder="例: 123-4567">
                    @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="searchAddress()">住所検索</button>
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
                    <input type="text" class="form-control" id="address2" name="address2" inputmode="text" value="{{ old('address2') }}">
                </div>
                <div class="col-md-4">
                    <label for="address3" class="form-label">町名・番地</label>
                    <input type="text" class="form-control" id="address3" name="address3" inputmode="text" value="{{ old('address3') }}">
                </div>
                <div class="col-md-4">
                    <label for="address4" class="form-label">建物名・部屋番号</label>
                    <input type="text" class="form-control" id="address4" name="address4" inputmode="text" value="{{ old('address4') }}">
                </div>
                <div class="col-md-4">
                    <label for="nearest_station" class="form-label">最寄り駅</label>
                    <input type="text" class="form-control" id="nearest_station" name="nearest_station" inputmode="text" value="{{ old('nearest_station') }}">
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <button type="button" class="btn btn-secondary btn-lg" onclick="showStep(1)">戻る</button>
            <button type="button" class="btn btn-primary btn-lg" onclick="showStep(3)">次へ</button>
        </div>
    </div>

    {{-- ステップ3: 学歴・職歴 --}}
    <div class="card step-card d-none" id="step3">
        <div class="card-header">
            <h5 class="mb-0">ステップ 3/5: 学歴・職歴</h5>
        </div>
        <div class="card-body">
            <h6 class="border-bottom pb-2 mb-3">学歴</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="education_level" class="form-label">学歴</label>
                    <select class="form-select" id="education_level" name="education_level">
                        <option value="">選択してください</option>
                        @foreach(['中学', '全日制高校', '定時制高校', '通信制高校', '高専', '専門学校', '大学', '短大', '大学院', 'その他'] as $level)
                            <option value="{{ $level }}" {{ old('education_level') === $level ? 'selected' : '' }}>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="education_status" class="form-label">学歴（状態）</label>
                    <select class="form-select" id="education_status" name="education_status">
                        <option value="">選択してください</option>
                        @foreach(['卒業', '中退', '在学中', '休学中'] as $status)
                            <option value="{{ $status }}" {{ old('education_status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="education_dropout_expected"
                               name="education_dropout_expected" value="1" {{ old('education_dropout_expected') ? 'checked' : '' }}>
                        <label class="form-check-label" for="education_dropout_expected">中退見込</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <label for="education_detail" class="form-label">学歴（詳細）</label>
                    <input type="text" class="form-control" id="education_detail" name="education_detail" inputmode="text" value="{{ old('education_detail') }}">
                </div>
            </div>

            <h6 class="border-bottom pb-2 mb-3">職歴</h6>
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="employment_type" class="form-label">雇用形態</label>
                    <select class="form-select" id="employment_type" name="employment_type">
                        <option value="">選択してください</option>
                        @foreach(['正社員・正規職員', '契約社員・嘱託社員', 'パート・アルバイト', '派遣社員', 'その他・詳細不明'] as $type)
                            <option value="{{ $type }}" {{ old('employment_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="employment_hours" class="form-label">週の労働時間</label>
                    <select class="form-select" id="employment_hours" name="employment_hours">
                        <option value="">選択してください</option>
                        @foreach(['週20時間以上', '週20時間未満', '不定期'] as $hours)
                            <option value="{{ $hours }}" {{ old('employment_hours') === $hours ? 'selected' : '' }}>{{ $hours }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="employment_period" class="form-label">雇用期間</label>
                    <select class="form-select" id="employment_period" name="employment_period">
                        <option value="">選択してください</option>
                        @foreach(['有期雇用（3ヶ月未満）', '有期雇用（3～6ヶ月未満）', '有期雇用（6ヶ月～1年未満）', '有期雇用（1年以上）', '無期雇用'] as $period)
                            <option value="{{ $period }}" {{ old('employment_period') === $period ? 'selected' : '' }}>{{ $period }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="unemployment_period" class="form-label">無職期間</label>
                    <select class="form-select" id="unemployment_period" name="unemployment_period">
                        <option value="">選択してください</option>
                        @foreach(['6ヶ月未満', '6ヶ月～1年', '1～3年', '3～5年', '5～10年', '10年以上'] as $period)
                            <option value="{{ $period }}" {{ old('unemployment_period') === $period ? 'selected' : '' }}>{{ $period }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label for="employment_detail" class="form-label">職歴（詳細）</label>
                    <textarea class="form-control" id="employment_detail" name="employment_detail" inputmode="text" rows="2">{{ old('employment_detail') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <button type="button" class="btn btn-secondary btn-lg" onclick="showStep(2)">戻る</button>
            <button type="button" class="btn btn-primary btn-lg" onclick="showStep(4)">次へ</button>
        </div>
    </div>

    {{-- ステップ4: 障害・医療情報 --}}
    <div class="card step-card d-none" id="step4">
        <div class="card-header">
            <h5 class="mb-0">ステップ 4/5: 障害・医療情報</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="disability_physical" class="form-label">身体障害者手帳</label>
                    <select class="form-select" id="disability_physical" name="disability_physical">
                        <option value="">選択してください</option>
                        <option value="あり" {{ old('disability_physical') === 'あり' ? 'selected' : '' }}>あり</option>
                        <option value="なし" {{ old('disability_physical') === 'なし' ? 'selected' : '' }}>なし</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="disability_physical_grade" class="form-label">級・取得時期など</label>
                    <input type="text" class="form-control" id="disability_physical_grade" name="disability_physical_grade" value="{{ old('disability_physical_grade') }}">
                </div>
                <div class="col-md-3">
                    <label for="disability_mental" class="form-label">精神障害者保健福祉手帳</label>
                    <select class="form-select" id="disability_mental" name="disability_mental">
                        <option value="">選択してください</option>
                        <option value="あり" {{ old('disability_mental') === 'あり' ? 'selected' : '' }}>あり</option>
                        <option value="なし" {{ old('disability_mental') === 'なし' ? 'selected' : '' }}>なし</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="disability_mental_grade" class="form-label">級・取得時期など</label>
                    <input type="text" class="form-control" id="disability_mental_grade" name="disability_mental_grade" value="{{ old('disability_mental_grade') }}">
                </div>
                <div class="col-md-3">
                    <label for="disability_intellectual" class="form-label">療育手帳</label>
                    <select class="form-select" id="disability_intellectual" name="disability_intellectual">
                        <option value="">選択してください</option>
                        <option value="あり" {{ old('disability_intellectual') === 'あり' ? 'selected' : '' }}>あり</option>
                        <option value="なし" {{ old('disability_intellectual') === 'なし' ? 'selected' : '' }}>なし</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="disability_intellectual_grade" class="form-label">級・取得時期など</label>
                    <input type="text" class="form-control" id="disability_intellectual_grade" name="disability_intellectual_grade" value="{{ old('disability_intellectual_grade') }}">
                </div>
                <div class="col-md-12">
                    <label for="disability_detail" class="form-label">障害者手帳（詳細）</label>
                    <textarea class="form-control" id="disability_detail" name="disability_detail" inputmode="text" rows="2">{{ old('disability_detail') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="hospital" class="form-label">通院先</label>
                    <textarea class="form-control" id="hospital" name="hospital" inputmode="text" rows="2">{{ old('hospital') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="medication" class="form-label">服薬</label>
                    <textarea class="form-control" id="medication" name="medication" inputmode="text" rows="2">{{ old('medication') }}</textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <button type="button" class="btn btn-secondary btn-lg" onclick="showStep(3)">戻る</button>
            <button type="button" class="btn btn-primary btn-lg" onclick="showStep(5)">次へ</button>
        </div>
    </div>

    {{-- ステップ5: 生活状況 --}}
    <div class="card step-card d-none" id="step5">
        <div class="card-header">
            <h5 class="mb-0">ステップ 5/5: 生活状況</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="financial_status" class="form-label">経済状態</label>
                    <select class="form-select" id="financial_status" name="financial_status">
                        <option value="">選択してください</option>
                        @foreach(['生活保護を受給している', '逼迫している', '特に困っていない'] as $fs)
                            <option value="{{ $fs }}" {{ old('financial_status') === $fs ? 'selected' : '' }}>{{ $fs }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9">
                    <label for="financial_detail" class="form-label">経済状態（詳細）</label>
                    <textarea class="form-control" id="financial_detail" name="financial_detail" inputmode="text" rows="1">{{ old('financial_detail') }}</textarea>
                </div>
                <div class="col-md-3">
                    <label for="hikikomori" class="form-label">ひきこもり経験</label>
                    <select class="form-select" id="hikikomori" name="hikikomori">
                        <option value="">選択してください</option>
                        <option value="あり" {{ old('hikikomori') === 'あり' ? 'selected' : '' }}>あり</option>
                        <option value="なし" {{ old('hikikomori') === 'なし' ? 'selected' : '' }}>なし</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="school_refusal" class="form-label">不登校経験</label>
                    <select class="form-select" id="school_refusal" name="school_refusal">
                        <option value="">選択してください</option>
                        <option value="あり" {{ old('school_refusal') === 'あり' ? 'selected' : '' }}>あり</option>
                        <option value="なし" {{ old('school_refusal') === 'なし' ? 'selected' : '' }}>なし</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="bullying" class="form-label">いじめを受けた経験</label>
                    <select class="form-select" id="bullying" name="bullying">
                        <option value="">選択してください</option>
                        <option value="あり" {{ old('bullying') === 'あり' ? 'selected' : '' }}>あり</option>
                        <option value="なし" {{ old('bullying') === 'なし' ? 'selected' : '' }}>なし</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <button type="button" class="btn btn-secondary btn-lg" onclick="showStep(4)">戻る</button>
            <button type="submit" class="btn btn-success btn-lg">登録</button>
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

            // 本人との関係に応じた姓の必須チェック
            var relationship = document.getElementById('family_relationship').value;
            clearNameError();
            if (relationship === '本人') {
                if (!document.getElementById('last_name').value.trim()) {
                    showNameError('last_name', '本人との関係が「本人」の場合、姓（本人）は必須です。');
                    valid = false;
                }
            } else if (relationship) {
                if (!document.getElementById('family_last_name').value.trim()) {
                    showNameError('family_last_name', '本人との関係が「本人以外」の場合、姓（家族など）は必須です。');
                    valid = false;
                }
            }

            // ひらがなバリデーション
            var hiraganaRegex = /^[\u3041-\u3093\u30FC\s\u3000]*$/;
            var kanaFields = [
                { id: 'last_name_kana', label: 'せい（本人）' },
                { id: 'first_name_kana', label: 'めい（本人）' },
                { id: 'family_last_name_kana', label: 'せい（家族など）' },
                { id: 'family_first_name_kana', label: 'めい（家族など）' },
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

        // 前進時のみステップ2（連絡先）のバリデーション
        if (currentStepNum === 2 && step > 2) {
            var valid = true;
            clearFieldError('email');
            var email = document.getElementById('email').value.trim();
            if (email !== '') {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showFieldError('email', 'メールアドレスの形式が正しくありません。');
                    valid = false;
                }
            }
            if (!valid) return;
        }

        // 全ステップを非表示
        document.querySelectorAll('.step-card').forEach(function(card) { card.classList.add('d-none'); });
        // 対象ステップを表示
        document.getElementById('step' + step).classList.remove('d-none');

        // プログレスバー更新
        document.getElementById('progressBar').style.width = (step * 100 / 5) + '%';

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
        ['last_name', 'family_last_name'].forEach(function(id) {
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
