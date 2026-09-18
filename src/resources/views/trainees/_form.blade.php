{{-- トレーニー登録・編集の共通フォーム（S-0308 / S-0310） --}}
{{-- 変数:
     - $trainee      : ?App\Models\Trainee   新規時は null、編集時は Trainee モデル
     - $client       : App\Models\Client     紐づく会員（表示のみ・変更不可）
     - $action       : string                フォーム送信先 URL
     - $method       : 'POST' | 'PUT'        PUT のときのみ @method('PUT') を出す
     - $submitLabel  : string                送信ボタン文言（例: '登録' / '更新'）
     - $cancelUrl    : string                キャンセル遷移先 URL
     - $pageTitle    : string                画面見出し（h2）文言
--}}
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ $pageTitle }}</h2>
        <div class="d-flex gap-2">
            <a href="{{ $cancelUrl }}" class="btn btn-secondary">キャンセル</a>
            <button type="submit" form="traineeForm" class="btn btn-success">{{ $submitLabel }}</button>
        </div>
    </div>

    <form method="POST" action="{{ $action }}" id="traineeForm"
          onkeydown="if(event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') { event.preventDefault(); }">
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

        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">基本情報</h6></div>
            <div class="card-body">
                {{-- 行1: 会員（表示のみ） --}}
                <div class="row g-3 mb-2">
                    <div class="col-md-8">
                        <div class="row g-2 align-items-center">
                            <label class="col-md-auto col-form-label text-md-end form-label-fixed">会員</label>
                            <div class="col-12 col-md">
                                <div class="form-control-plaintext">{{ $client->full_name }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 行2: 名前 + 犬種 --}}
                <div class="row g-3 mb-2">
                    <div class="col-md-5">
                        <div class="row g-2 align-items-center">
                            <label for="name" class="col-md-auto col-form-label text-md-end form-label-fixed">
                                名前 <span class="text-danger">*</span>
                            </label>
                            <div class="col-12 col-md">
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       id="name" name="name" maxlength="50" required
                                       value="{{ old('name', $trainee?->name) }}" autocomplete="off">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="row g-2 align-items-center">
                            <label for="breed" class="col-md-auto col-form-label text-md-end form-label-fixed">犬種</label>
                            <div class="col-12 col-md">
                                <input type="text" class="form-control @error('breed') is-invalid @enderror"
                                       id="breed" name="breed" maxlength="100"
                                       value="{{ old('breed', $trainee?->breed) }}" autocomplete="off">
                                @error('breed') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 行3: 性別 + 誕生日 --}}
                <div class="row g-3 mb-2">
                    <div class="col-md-5">
                        <div class="row g-2 align-items-center">
                            <label for="sex" class="col-md-auto col-form-label text-md-end form-label-fixed">性別</label>
                            <div class="col-12 col-md">
                                <select class="form-select @error('sex') is-invalid @enderror"
                                        id="sex" name="sex" autocomplete="off">
                                    <option value=""></option>
                                    @foreach(\App\Models\Trainee::sexLabels() as $value => $label)
                                        <option value="{{ $value }}" {{ old('sex', $trainee?->sex) === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('sex') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="row g-2 align-items-center">
                            <label for="birth_date" class="col-md-auto col-form-label text-md-end form-label-fixed">誕生日</label>
                            <div class="col-12 col-md">
                                <input type="text" class="form-control datepicker @error('birth_date') is-invalid @enderror"
                                       id="birth_date" name="birth_date"
                                       value="{{ old('birth_date', $trainee?->birth_date?->format('Y-m-d')) }}"
                                       placeholder="例: 2020-05-01" pattern="\d{4}-\d{2}-\d{2}" maxlength="10"
                                       autocomplete="off">
                                @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 行4: 備考 --}}
                <div class="row g-3">
                    <div class="col-12">
                        <div class="row g-2 align-items-start">
                            <label for="note" class="col-md-auto col-form-label text-md-end form-label-fixed">備考</label>
                            <div class="col-12 col-md">
                                <textarea class="form-control @error('note') is-invalid @enderror"
                                          id="note" name="note" rows="3">{{ old('note', $trainee?->note) }}</textarea>
                                @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
