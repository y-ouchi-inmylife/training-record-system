{{-- クライアント登録・編集共通フォーム --}}
{{-- 変数:
     - $client       : ?App\Models\Client   新規時は null、編集時は Client モデル
     - $trainers     : Collection<Trainer>  主担当プルダウン用（実務トレーナーのみ）
     - $action       : string               フォーム送信先 URL
     - $method       : 'POST' | 'PUT'       PUT のときのみ @method('PUT') を出す
     - $submitLabel  : string               送信ボタン文言（例: '登録' / '更新'）
     - $cancelUrl    : string               キャンセル遷移先 URL
     - $pageTitle    : string               画面見出し（h2）文言
--}}
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ $pageTitle }}</h2>
        <div class="d-flex gap-2">
            <a href="{{ $cancelUrl }}" class="btn btn-secondary js-leave-link">キャンセル</a>
            <button type="submit" form="clientForm" class="btn btn-success">{{ $submitLabel }}</button>
        </div>
    </div>

    <form method="POST" action="{{ $action }}" id="clientForm"
          onkeydown="if(event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') { event.preventDefault(); }"
          onsubmit="return validateBeforeSubmit()">
        @csrf
        @if($method === 'PUT')
            @method('PUT')
        @endif

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

        {{-- カテゴリー1: 基本情報 --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">基本情報</h6></div>
            <div class="card-body">
                <div class="row g-3 mb-2">
                    {{-- 行1: (編集時のみ 内部ID +) 初回日 + メールアドレス --}}
                    @if($client)
                        <div class="col-md-3">
                            <div class="row g-2 align-items-center">
                                <label for="internal_id" class="col-md-auto col-form-label text-md-end form-label-fixed">
                                    内部ID <span class="text-danger">*</span>
                                </label>
                                <div class="col-12 col-md">
                                    <input type="text" class="form-control @error('internal_id') is-invalid @enderror"
                                           id="internal_id" name="internal_id"
                                           value="{{ old('internal_id', $client->internal_id) }}" maxlength="10" required
                                           autocomplete="off">
                                    @error('internal_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <div class="row g-2 align-items-center">
                            <label for="initial_consultation_date" class="col-md-auto col-form-label text-md-end form-label-fixed">
                                初回日 <span class="text-danger">*</span>
                            </label>
                            <div class="col-12 col-md">
                                <input type="text" class="form-control datepicker @error('initial_consultation_date') is-invalid @enderror"
                                       id="initial_consultation_date" name="initial_consultation_date"
                                       value="{{ old('initial_consultation_date', $client?->initial_consultation_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required
                                       placeholder="例: 2000-01-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10"
                                       autocomplete="off">
                                @error('initial_consultation_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row g-2 align-items-center">
                            <label for="email" class="col-md-auto col-form-label text-md-end form-label-fixed">メールアドレス</label>
                            <div class="col-12 col-md">
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email', $client?->email) }}"
                                       autocomplete="off">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-2">
                    {{-- 行2: 名前(姓+名) + ふりがな(せい+めい) --}}
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
                                               value="{{ old('last_name', $client?->last_name) }}" placeholder="姓"
                                               autocomplete="off">
                                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                               id="first_name" name="first_name" inputmode="text"
                                               value="{{ old('first_name', $client?->first_name) }}" placeholder="名"
                                               autocomplete="off">
                                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                                               value="{{ old('last_name_kana', $client?->last_name_kana) }}" placeholder="せい"
                                               autocomplete="off">
                                        @error('last_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="text" class="form-control @error('first_name_kana') is-invalid @enderror"
                                               id="first_name_kana" name="first_name_kana" inputmode="hiragana"
                                               value="{{ old('first_name_kana', $client?->first_name_kana) }}" placeholder="めい"
                                               autocomplete="off">
                                        @error('first_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    {{-- 行3: 生年月日 + 性別 + 主担当 --}}
                    <div class="col-md-3">
                        <div class="row g-2 align-items-center">
                            <label for="birth_date" class="col-md-auto col-form-label text-md-end form-label-fixed">生年月日</label>
                            <div class="col-12 col-md">
                                <input type="text" class="form-control datepicker" id="birth_date" name="birth_date"
                                       value="{{ old('birth_date', $client?->birth_date?->format('Y-m-d')) }}"
                                       placeholder="例: 2000-01-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10"
                                       autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row g-2 align-items-center">
                            <label for="gender" class="col-md-auto col-form-label text-md-end form-label-fixed">性別</label>
                            <div class="col-12 col-md">
                                <select class="form-select" id="gender" name="gender" autocomplete="off">
                                    <option value=""></option>
                                    @foreach(['男', '女', '無回答'] as $g)
                                        <option value="{{ $g }}" {{ old('gender', $client?->gender) === $g ? 'selected' : '' }}>{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row g-2 align-items-center">
                            <label for="primary_trainer_id" class="col-md-auto col-form-label text-md-end form-label-fixed">主担当</label>
                            <div class="col-12 col-md">
                                <select class="form-select" id="primary_trainer_id" name="primary_trainer_id" autocomplete="off">
                                    <option value=""></option>
                                    @foreach($trainers as $trainer)
                                        <option value="{{ $trainer->id }}" {{ old('primary_trainer_id', $client?->primary_trainer_id) == $trainer->id ? 'selected' : '' }}>
                                            {{ $trainer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- カテゴリー2: 連絡先 --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">連絡先</h6></div>
            <div class="card-body">
                <div class="row g-3 mb-2">
                    {{-- 行1: 電話番号1 + 電話番号2 --}}
                    <div class="col-md-3">
                        <div class="row g-2 align-items-center">
                            <label for="phone1" class="col-md-auto col-form-label text-md-end form-label-fixed">電話番号1</label>
                            <div class="col-12 col-md">
                                <input type="tel" class="form-control @error('phone1') is-invalid @enderror"
                                       id="phone1" name="phone1" value="{{ old('phone1', $client?->phone1) }}" placeholder="例: 090-1234-5678"
                                       autocomplete="off">
                                @error('phone1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row g-2 align-items-center">
                            <label for="phone2" class="col-md-auto col-form-label text-md-end form-label-fixed">電話番号2</label>
                            <div class="col-12 col-md">
                                <input type="tel" class="form-control @error('phone2') is-invalid @enderror"
                                       id="phone2" name="phone2" value="{{ old('phone2', $client?->phone2) }}" placeholder="例: 090-1234-5678"
                                       autocomplete="off">
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
                                           id="postal_code" name="postal_code" value="{{ old('postal_code', $client?->postal_code) }}"
                                           autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary" id="btn-search-address" onclick="searchAddress()">検索</button>
                                    @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row g-2 align-items-center">
                            <label for="address1" class="col-md-auto col-form-label text-md-end form-label-fixed">都道府県</label>
                            <div class="col-12 col-md">
                                <select class="form-select" id="address1" name="address1" autocomplete="off">
                                    <option value=""></option>
                                    @foreach(['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'] as $pref)
                                        <option value="{{ $pref }}" {{ old('address1', $client?->address1) == $pref ? 'selected' : '' }}>{{ $pref }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row g-2 align-items-center">
                            <label for="address2" class="col-md-auto col-form-label text-md-end form-label-fixed">市区町村</label>
                            <div class="col-12 col-md">
                                <input type="text" class="form-control" id="address2" name="address2" inputmode="text" value="{{ old('address2', $client?->address2) }}" autocomplete="off">
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
                                <input type="text" class="form-control" id="address3" name="address3" inputmode="text" value="{{ old('address3', $client?->address3) }}" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row g-2 align-items-center">
                            <label for="address4" class="col-md-auto col-form-label text-md-end form-label-fixed">建物名・部屋番号</label>
                            <div class="col-12 col-md">
                                <input type="text" class="form-control" id="address4" name="address4" inputmode="text" value="{{ old('address4', $client?->address4) }}" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ボタン --}}
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ $cancelUrl }}" class="btn btn-secondary js-leave-link">キャンセル</a>
            <button type="submit" class="btn btn-success">{{ $submitLabel }}</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 未保存変更警告
        new window.UnsavedChangesGuard({
            formSelector: '#clientForm',
            leaveLinkSelector: '.js-leave-link'
        }).init();
    });

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

    // 送信時バリデーション（姓必須・ふりがなのひらがなチェック・メール形式）
    function validateBeforeSubmit() {
        let valid = true;

        // 姓必須
        clearFieldError('last_name');
        if (!document.getElementById('last_name').value.trim()) {
            showFieldError('last_name', '姓は必須です。');
            valid = false;
        }

        // ふりがな（ひらがな）
        const hiraganaRegex = /^[ぁ-んー\s　]*$/;
        [{id: 'last_name_kana', label: 'せい'}, {id: 'first_name_kana', label: 'めい'}].forEach(function (f) {
            clearFieldError(f.id);
            const v = document.getElementById(f.id).value.trim();
            if (v !== '' && !hiraganaRegex.test(v)) {
                showFieldError(f.id, f.label + 'はひらがなで入力してください。');
                valid = false;
            }
        });

        // メール形式
        clearFieldError('email');
        const email = document.getElementById('email').value.trim();
        if (email !== '') {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!re.test(email)) {
                showFieldError('email', 'メールアドレスの形式が正しくありません。');
                valid = false;
            }
        }

        return valid;
    }

    // 住所検索（郵便番号→zipcloud）
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
                btn.textContent = '検索';
            });
    }
</script>
@endpush
