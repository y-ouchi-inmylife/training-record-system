{{-- 計測値の登録・編集モーダル（S-0309 で使用、登録と編集で共用）。
     登録・編集で 1 つのモーダルを使い、JavaScript でフォームの `action` /
     `_method` / 各入力値を差し替える。

     利用方法:
       @include('trainees._measurement-modal')
       // ボタン側:
       //   新規登録: onclick="window.measurementModal.openForCreate()"
       //   編集   : onclick="window.measurementModal.openForEdit(this.dataset)"

     期待する変数:
       - $trainee : App\Models\Trainee   親トレーニー（store URL 生成に使う）

     **新規登録の初期日時はコントローラから渡さない**（設計書 S-0309「新規登録時の
     初期値」参照）。以前はコントローラの `now()` で組み立てた値を `$defaultMeasuredDate`
     / `$defaultMeasuredTime` として渡していたが、ページ読み込み時に確定するため
     「画面を開いたまま時間が経ってからモーダルを開くと古い日時が入る」不具合が
     発生した。JavaScript の `new Date()` で `openForCreate()` の中で現在時刻を
     組み立てる形に変更した（**モーダルを開いた瞬間のブラウザ時刻**を使う）。

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
                                   value="{{ old('measured_date') }}"
                                   placeholder="例: 2026-09-15" pattern="\d{4}-\d{2}-\d{2}" maxlength="10"
                                   required autocomplete="off">
                            @error('measured_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="measured_time" class="form-label">計測時刻 <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('measured_time') is-invalid @enderror"
                                   id="measured_time" name="measured_time"
                                   value="{{ old('measured_time') }}"
                                   required autocomplete="off">
                            @error('measured_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="weight_kg" class="form-label">体重（kg） <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="999.99"
                                   class="form-control @error('weight_kg') is-invalid @enderror"
                                   id="weight_kg" name="weight_kg"
                                   value="{{ old('weight_kg') }}"
                                   placeholder="例: 12.35"
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

    // 新規登録時の初期日時は `openForCreate()` 内で **`new Date()` から組み立てる**
    // （設計書 S-0309「新規登録時の初期値」参照）。以前は $defaultMeasuredDate /
    // $defaultMeasuredTime としてコントローラから渡していたが、ページ読み込み時に
    // 値が確定するため画面を開いたまま時間が経つと古い日時が入る不具合があった。
    // モーダルを開く瞬間の**ブラウザ時刻**を使うため、JS 側でその都度組み立てる。
    function pad2(n) { return String(n).padStart(2, '0'); }
    function nowDateString() {
        // 'YYYY-MM-DD' を返す（ブラウザのローカル時刻）。ゼロ埋め必須
        // （「2026-9-5」ではなく「2026-09-05」）。
        const d = new Date();
        return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
    }
    function nowTimeString() {
        // 'HH:MM' を返す（ブラウザのローカル時刻）。ゼロ埋め必須（「9:5」ではなく「09:05」）。
        const d = new Date();
        return `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
    }

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
        // モーダルを開いた瞬間の日時を初期値にする（設計書 S-0309 参照）。
        dateInput.value = nowDateString();
        timeInput.value = nowTimeString();
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
