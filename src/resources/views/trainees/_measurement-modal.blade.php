{{-- 計測値の登録・編集モーダル（S-0309 で使用、登録と編集で共用）。
     登録・編集で 1 つのモーダルを使い、JavaScript でフォームの `action` /
     `_method` / 各入力値を差し替える。

     利用方法:
       @include('trainees._measurement-modal')
       // ボタン側:
       //   新規登録: onclick="window.measurementModal.openForCreate()"
       //   編集   : onclick="window.measurementModal.openForEdit(this.dataset)"

     期待する変数:
       - $trainee               : App\Models\Trainee   親トレーニー（store URL 生成に使う）
       - $defaultMeasuredDate   : string ('Y-m-d')    新規登録時の初期値
       - $defaultMeasuredTime   : string ('H:i')      新規登録時の初期値

     バリデーションエラー時：Laravel は back で redirect し、old() と $errors が
     セッションに載る。以下の順で再描画時にモーダルを自動で開き直す：
       1. 計測値フィールドに関するエラーがあるか（$errors->hasAny([...]) で判定）
       2. old('_measurement_id') の有無で「編集モードで開き直す」か「登録モードで開き直す」を分岐
--}}

@php
    // 計測値フォームに関するエラーがあるかを Blade 側で判定してから JS に渡す。
    // トレーニー本体のエラー（トレーニー編集フォームで発生）とは区別する。
    $measurementFieldNames = ['measured_date', 'measured_time', 'weight_kg'];
    $hasMeasurementError = $errors->hasAny($measurementFieldNames);
    // 編集で失敗した場合は old('_measurement_id') に対象レコードの id が入る。
    // route() で update URL を再構築できるよう Blade から渡す。
    $oldMeasurementId = old('_measurement_id');
    $storeUrl = route('trainee-measurements.store', $trainee);
@endphp

<div class="modal fade" id="measurementModal" tabindex="-1" aria-labelledby="measurementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="measurementForm" method="POST" action="{{ $storeUrl }}">
                @csrf
                {{-- _method は JavaScript で「（空）」と 'PUT' を切り替える。
                     `@method('PUT')` の代わりに hidden input を JS で操作する。 --}}
                <input type="hidden" name="_method" id="measurementFormMethodInput" value="">
                {{-- 編集時は対象レコードの id をここに載せる。バリデーションエラーで
                     再描画されたときに、old('_measurement_id') から編集モードで開き直す判定に使う。 --}}
                <input type="hidden" name="_measurement_id" id="measurementFormIdInput" value="">

                <div class="modal-header">
                    <h5 class="modal-title" id="measurementModalLabel">計測値の登録</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>

                <div class="modal-body">
                    {{-- エラーサマリ：バリデーションエラーの内容を上部に一覧表示 --}}
                    @if($hasMeasurementError)
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($measurementFieldNames as $field)
                                    @error($field)
                                        <li>{{ $message }}</li>
                                    @enderror
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="measured_date" class="form-label">計測日 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker @error('measured_date') is-invalid @enderror"
                                   id="measured_date" name="measured_date"
                                   value="{{ old('measured_date', $defaultMeasuredDate) }}"
                                   placeholder="例: 2026-09-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10"
                                   required autocomplete="off">
                            @error('measured_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="measured_time" class="form-label">計測時刻 <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('measured_time') is-invalid @enderror"
                                   id="measured_time" name="measured_time"
                                   value="{{ old('measured_time', $defaultMeasuredTime) }}"
                                   required autocomplete="off">
                            @error('measured_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="weight_kg" class="form-label">体重（kg） <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="999.99"
                                   class="form-control @error('weight_kg') is-invalid @enderror"
                                   id="weight_kg" name="weight_kg"
                                   value="{{ old('weight_kg') }}"
                                   required autocomplete="off">
                            @error('weight_kg') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-success" id="measurementSubmitBtn">登録</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    // 二重登録防止（部品として複数箇所から include された場合の保険）。
    if (window.measurementModal && typeof window.measurementModal.openForCreate === 'function') return;

    // Blade から埋め込む定数
    const STORE_URL = @json($storeUrl);
    // 編集時の URL テンプレート。プレースホルダを実 ID で置換する。
    const UPDATE_URL_TEMPLATE = @json(route('trainee-measurements.update', ['measurement' => '__ID__']));
    const DEFAULT_MEASURED_DATE = @json($defaultMeasuredDate);
    const DEFAULT_MEASURED_TIME = @json($defaultMeasuredTime);

    let modalEl = null;
    let modal = null;
    let formEl = null;
    let methodInput = null;
    let idInput = null;
    let submitBtn = null;
    let titleEl = null;
    let dateInput = null;
    let timeInput = null;
    let weightInput = null;

    function initRefs() {
        if (modalEl) return;
        modalEl = document.getElementById('measurementModal');
        if (!modalEl) return;
        modal = new bootstrap.Modal(modalEl);
        formEl = document.getElementById('measurementForm');
        methodInput = document.getElementById('measurementFormMethodInput');
        idInput = document.getElementById('measurementFormIdInput');
        submitBtn = document.getElementById('measurementSubmitBtn');
        titleEl = document.getElementById('measurementModalLabel');
        dateInput = document.getElementById('measured_date');
        timeInput = document.getElementById('measured_time');
        weightInput = document.getElementById('weight_kg');
    }

    function openForCreate() {
        initRefs();
        if (!modal) return;
        formEl.action = STORE_URL;
        methodInput.value = '';
        idInput.value = '';
        titleEl.textContent = '計測値の登録';
        submitBtn.textContent = '登録';
        dateInput.value = DEFAULT_MEASURED_DATE;
        timeInput.value = DEFAULT_MEASURED_TIME;
        weightInput.value = '';
        modal.show();
    }

    function openForEdit(data) {
        // data: { id, measuredDate, measuredTime, weightKg }
        //   ボタンの dataset から渡す（data-* 属性はキャメルケースになる）
        initRefs();
        if (!modal) return;
        formEl.action = UPDATE_URL_TEMPLATE.replace('__ID__', encodeURIComponent(data.id));
        methodInput.value = 'PUT';
        idInput.value = data.id;
        titleEl.textContent = '計測値の編集';
        submitBtn.textContent = '更新';
        dateInput.value = data.measuredDate || '';
        timeInput.value = data.measuredTime || '';
        weightInput.value = data.weightKg || '';
        modal.show();
    }

    window.measurementModal = { openForCreate: openForCreate, openForEdit: openForEdit };

    // バリデーションエラーで再描画されたときにモーダルを自動で開き直す。
    // Blade 側で hasMeasurementError を判定して JS に渡している。
    document.addEventListener('DOMContentLoaded', function () {
        initRefs();
        @if($hasMeasurementError)
            const oldMeasurementId = @json($oldMeasurementId);
            if (oldMeasurementId) {
                // 編集モードで開き直す。入力値は old() で既に埋まっているので、
                // フォームの action / _method / _measurement_id をセットしてタイトルを更新するだけ。
                formEl.action = UPDATE_URL_TEMPLATE.replace('__ID__', encodeURIComponent(oldMeasurementId));
                methodInput.value = 'PUT';
                idInput.value = oldMeasurementId;
                titleEl.textContent = '計測値の編集';
                submitBtn.textContent = '更新';
            } else {
                // 登録モードで開き直す。フォームは初期状態のまま（action = STORE_URL、_method 空）。
                titleEl.textContent = '計測値の登録';
                submitBtn.textContent = '登録';
            }
            modal.show();
        @endif
    });
})();
</script>
@endpush
