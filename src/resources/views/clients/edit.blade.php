@extends('layouts.app')

@section('title', 'クライアント編集')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">クライアント編集</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary js-leave-link">キャンセル</a>
            <button type="submit" form="clientEditForm" class="btn btn-success">更新</button>
        </div>
    </div>

    <form method="POST" action="{{ route('clients.update', $client) }}" id="clientEditForm"
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

        {{-- カテゴリー1: 基本情報 --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">基本情報</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="internal_id" class="form-label">内部ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('internal_id') is-invalid @enderror"
                               id="internal_id" name="internal_id"
                               value="{{ old('internal_id', $client->internal_id) }}" maxlength="10" required>
                        @error('internal_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="initial_consultation_date" class="form-label">初回日 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control datepicker @error('initial_consultation_date') is-invalid @enderror"
                               id="initial_consultation_date" name="initial_consultation_date"
                               value="{{ old('initial_consultation_date', $client->initial_consultation_date?->format('Y-m-d')) }}" required
                               placeholder="例: 2000-01-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10">
                        @error('initial_consultation_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="w-100 mt-0"></div>

                    <div class="col-md-3">
                        <label for="last_name" class="form-label">姓 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                               id="last_name" name="last_name" inputmode="text" value="{{ old('last_name', $client->last_name) }}">
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="first_name" class="form-label">名</label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                               id="first_name" name="first_name" inputmode="text" value="{{ old('first_name', $client->first_name) }}">
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="last_name_kana" class="form-label">せい</label>
                        <input type="text" class="form-control @error('last_name_kana') is-invalid @enderror"
                               id="last_name_kana" name="last_name_kana" inputmode="hiragana" value="{{ old('last_name_kana', $client->last_name_kana) }}"
                               placeholder="例: やまだ">
                        @error('last_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="first_name_kana" class="form-label">めい</label>
                        <input type="text" class="form-control @error('first_name_kana') is-invalid @enderror"
                               id="first_name_kana" name="first_name_kana" inputmode="hiragana" value="{{ old('first_name_kana', $client->first_name_kana) }}"
                               placeholder="例: たろう">
                        @error('first_name_kana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="birth_date" class="form-label">生年月日</label>
                        <input type="text" class="form-control datepicker" id="birth_date" name="birth_date"
                               value="{{ old('birth_date', $client->birth_date?->format('Y-m-d')) }}"
                               placeholder="例: 2000-01-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10">
                    </div>
                    <div class="col-md-3">
                        <label for="gender" class="form-label">性別</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="">選択してください</option>
                            @foreach(['男', '女', 'その他'] as $g)
                                <option value="{{ $g }}" {{ old('gender', $client->gender) === $g ? 'selected' : '' }}>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email', $client->email) }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- カテゴリー7: 支援管理 --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">支援管理</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="primary_trainer_id" class="form-label">主担当</label>
                        <select class="form-select" id="primary_trainer_id" name="primary_trainer_id">
                            <option value="">選択してください</option>
                            @foreach($trainers as $trainer)
                                <option value="{{ $trainer->id }}" {{ old('primary_trainer_id', $client->primary_trainer_id) == $trainer->id ? 'selected' : '' }}>
                                    {{ $trainer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- カテゴリー2: 連絡先 --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">連絡先</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- 行1: 電話番号 --}}
                    <div class="col-md-3">
                        <label for="phone1" class="form-label">電話番号1</label>
                        <input type="tel" class="form-control @error('phone1') is-invalid @enderror"
                               id="phone1" name="phone1" value="{{ old('phone1', $client->phone1) }}" placeholder="例: 090-1234-5678">
                        @error('phone1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label for="phone2" class="form-label">電話番号2</label>
                        <input type="tel" class="form-control @error('phone2') is-invalid @enderror"
                               id="phone2" name="phone2" value="{{ old('phone2', $client->phone2) }}" placeholder="例: 090-1234-5678">
                        @error('phone2') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    {{-- 行2: 郵便番号 + 住所検索 + 都道府県 + 市区町村 --}}
                    <div class="col-md-2">
                        <label for="postal_code" class="form-label">郵便番号</label>
                        <input type="text" class="form-control @error('postal_code') is-invalid @enderror"
                               id="postal_code" name="postal_code" value="{{ old('postal_code', $client->postal_code) }}"
                               placeholder="例: 123-4567">
                        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btn-search-address" onclick="searchAddress()">住所検索</button>
                    </div>
                    <div class="col-md-3">
                        <label for="address1" class="form-label">都道府県</label>
                        <select class="form-select" id="address1" name="address1">
                            <option value="">選択してください</option>
                            @foreach(['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'] as $pref)
                                <option value="{{ $pref }}" {{ old('address1', $client->address1) == $pref ? 'selected' : '' }}>{{ $pref }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="address2" class="form-label">市区町村</label>
                        <input type="text" class="form-control" id="address2" name="address2" inputmode="text" value="{{ old('address2', $client->address2) }}">
                    </div>
                    {{-- 行3: 町名・番地 + 建物名 + 最寄り駅 --}}
                    <div class="col-md-4">
                        <label for="address3" class="form-label">町名・番地</label>
                        <input type="text" class="form-control" id="address3" name="address3" inputmode="text" value="{{ old('address3', $client->address3) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="address4" class="form-label">建物名・部屋番号</label>
                        <input type="text" class="form-control" id="address4" name="address4" inputmode="text" value="{{ old('address4', $client->address4) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ボタン --}}
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary js-leave-link">キャンセル</a>
            <button type="submit" class="btn btn-success">更新</button>
        </div>
    </form>
</div>
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
            formSelector: '#clientEditForm',
            leaveLinkSelector: '.js-leave-link'
        }).init();
    });
</script>
<script>
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
@endpush
