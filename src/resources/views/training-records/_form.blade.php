{{-- トレーニング記録登録・編集共通フォーム（クライアントは常に固定モード） --}}
<form method="POST" action="{{ $action }}" id="trainingRecordForm" novalidate>
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif

    {{-- 基本情報 --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">基本情報</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                {{-- クライアント（クライアント詳細からの遷移で確定：変更不可） --}}
                <div class="col-md-6">
                    <label class="form-label">クライアント <span class="text-danger">*</span></label>
                    @php $fixedClient = $selectedClient ?? $record->client; @endphp
                    <input type="text" class="form-control bg-light"
                           value="{{ $fixedClient->internal_id }} {{ $fixedClient->display_name }}"
                           data-client-display-name="{{ $fixedClient->display_name }}"
                           readonly>
                    <input type="hidden" name="client_id" value="{{ $fixedClient->id }}">
                </div>

                {{-- トレーニング日 --}}
                <div class="col-md-3">
                    <label for="training_date" class="form-label">トレーニング日 <span class="text-danger">*</span></label>
                    <input type="text" name="training_date" id="training_date"
                        class="form-control datepicker @error('training_date') is-invalid @enderror"
                        value="{{ old('training_date', $record?->training_date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                        placeholder="例: 2026-04-01" pattern="\d{4}-\d{2}-\d{2}" maxlength="10" required>
                    @error('training_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- トレーニング時刻 --}}
                <div class="col-md-3">
                    <label for="training_time" class="form-label">トレーニング時刻</label>
                    <input type="time" name="training_time" id="training_time"
                        class="form-control @error('training_time') is-invalid @enderror"
                        value="{{ old('training_time', $record?->training_time ? substr($record->training_time, 0, 5) : '') }}">
                    @error('training_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                {{-- 担当1 --}}
                <div class="col-md-4">
                    <label for="trainer1_id" class="form-label">担当1 <span class="text-danger">*</span></label>
                    <select name="trainer1_id" id="trainer1_id" class="form-select @error('trainer1_id') is-invalid @enderror" required>
                        <option value="">選択してください</option>
                        @foreach($trainers as $trainer)
                            <option value="{{ $trainer->id }}"
                                {{ old('trainer1_id', $record?->trainer1_id ?? auth()->id()) == $trainer->id ? 'selected' : '' }}>
                                {{ $trainer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('trainer1_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- 担当2 --}}
                <div class="col-md-4">
                    <label for="trainer2_id" class="form-label">担当2</label>
                    <select name="trainer2_id" id="trainer2_id" class="form-select @error('trainer2_id') is-invalid @enderror">
                        <option value="">なし</option>
                        @foreach($trainers as $trainer)
                            <option value="{{ $trainer->id }}"
                                {{ old('trainer2_id', $record?->trainer2_id) == $trainer->id ? 'selected' : '' }}>
                                {{ $trainer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('trainer2_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="row g-3 mt-1">
            </div>
        </div>
    </div>

    {{-- トレーニング内容 --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">トレーニング内容</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                {{-- トレーニング内容 --}}
                <div class="col-md-4">
                    <label for="training_type_id" class="form-label">トレーニング内容</label>
                    <select name="training_type_id" id="training_type_id" class="form-select @error('training_type_id') is-invalid @enderror">
                        <option value="">選択してください</option>
                        @foreach($trainingTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ old('training_type_id', $record?->training_type_id) == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('training_type_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- トレーニング内容（詳細） --}}
                <div class="col-md-8">
                    <label for="training_detail" class="form-label">トレーニング内容（詳細）</label>
                    <input type="text" name="training_detail" id="training_detail"
                        class="form-control @error('training_detail') is-invalid @enderror"
                        inputmode="text" value="{{ old('training_detail', $record?->training_detail) }}"
                        maxlength="255" placeholder="主旨">
                    @error('training_detail')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- フェーズ --}}
                <div class="col-md-4">
                    <label for="phase_id" class="form-label">フェーズ</label>
                    <select name="phase_id" id="phase_id" class="form-select @error('phase_id') is-invalid @enderror">
                        <option value="">選択してください</option>
                        @foreach($phases as $phase)
                            <option value="{{ $phase->id }}"
                                {{ old('phase_id', $record?->phase_id) == $phase->id ? 'selected' : '' }}>
                                {{ $phase->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('phase_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- トレーニング記録 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">トレーニング記録 <span class="text-muted">（事実を客観的に記録）</span></h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="insertSummaryBtn">
                音声記録の要約から入力
            </button>
        </div>
        <div class="card-body">
            <textarea name="record_content" id="record_content" rows="8"
                class="form-control @error('record_content') is-invalid @enderror"
                inputmode="text">{{ old('record_content', $record?->record_content) }}</textarea>
            @error('record_content')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- 所感 --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">所感 <span class="text-muted">（トレーナー間共有・クライアント非開示）</span></h6>
        </div>
        <div class="card-body">
            <textarea name="impression" id="impression" rows="4"
                class="form-control @error('impression') is-invalid @enderror"
                inputmode="text">{{ old('impression', $record?->impression) }}</textarea>
            @error('impression')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- 送信ボタン --}}
    <div class="d-flex justify-content-end gap-2 mb-4">
        @if($record)
            <a href="{{ route('clients.show', $record->client_id) }}" class="btn btn-secondary js-leave-link">キャンセル</a>
        @else
            <a href="{{ $selectedClientId ? route('clients.show', $selectedClientId) : route('training-records.index') }}" class="btn btn-secondary js-leave-link">キャンセル</a>
        @endif
        <button type="submit" class="btn btn-success">{{ $record ? '更新' : '登録' }}</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // フォーム送信時に必須チェックを実行（上部・下部ボタン共通）
    const form = document.getElementById('trainingRecordForm');
    form.addEventListener('submit', function(e) {
        var errors = [];
        var firstInvalidElement = null;

        // 既存のエラーサマリーを削除
        var existingSummary = form.querySelector('.validation-error-summary');
        if (existingSummary) existingSummary.remove();

        // フォームの入力欄順にバリデーション実行するためのヘルパー
        function checkRequired(fieldId, errorMessage) {
            var field = document.getElementById(fieldId);
            if (field && !field.value) {
                field.classList.add('is-invalid');
                errors.push(errorMessage);
                if (!firstInvalidElement) firstInvalidElement = field;
            } else if (field) {
                field.classList.remove('is-invalid');
            }
        }

        // 1. クライアント（Select2で非表示のため個別に処理）
        var clientSelect = form.querySelector('select[name="client_id"]');
        var clientHidden = form.querySelector('input[type="hidden"][name="client_id"]');
        if (clientSelect) {
            var clientErrorDiv = form.querySelector('.client-id-error');
            if (!clientSelect.value) {
                if (clientErrorDiv) clientErrorDiv.style.display = 'block';
                errors.push('クライアントを選択してください。');
                if (!firstInvalidElement) firstInvalidElement = clientSelect.closest('.col-md-6');
            } else {
                if (clientErrorDiv) clientErrorDiv.style.display = 'none';
            }
        } else if (!clientHidden) {
            errors.push('クライアントを選択してください。');
        }

        // 2. トレーニング日
        checkRequired('training_date', 'トレーニング日を入力してください。');

        // 4. 担当1
        checkRequired('trainer1_id', '担当1を選択してください。');

        // 5. 参加状況

        // 6. 参加形態

        // エラーがある場合はフォーム送信をブロックし、エラーサマリーを表示
        if (errors.length > 0) {
            e.preventDefault();

            var summary = document.createElement('div');
            summary.className = 'alert alert-danger validation-error-summary';
            summary.innerHTML = '<strong>入力内容にエラーがあります。</strong><ul class="mb-0 mt-1">' +
                errors.map(function(err) { return '<li>' + err + '</li>'; }).join('') + '</ul>';
            form.insertBefore(summary, form.firstChild);

            // エラーサマリーにスクロール
            summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});
</script>

{{-- 要約選択モーダル --}}
<div class="modal fade" id="summaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="summaryModalTitle">音声記録の要約から入力</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- 検索 --}}
                <div class="mb-3">
                    <input type="text" class="form-control" id="summarySearch" placeholder="表示名で検索...">
                </div>

                {{-- ローディング --}}
                <div id="summaryLoading" class="text-center d-none">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">読み込み中...</span>
                    </div>
                </div>

                {{-- 要約一覧 --}}
                <div id="summaryList"></div>

                {{-- エラーメッセージ --}}
                <div id="summaryError" class="alert alert-danger d-none"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const summaryModal = new bootstrap.Modal(document.getElementById('summaryModal'));
    let summaries = [];

    // 「要約から入力」ボタンクリック
    document.getElementById('insertSummaryBtn').addEventListener('click', function() {
        // 現在選択中の client_id を取得（hidden input から）
        var clientIdEl = document.querySelector('[name="client_id"]');
        var clientId = clientIdEl ? clientIdEl.value : '';
        if (!clientId) {
            // 通常は固定クライアントが必ずあるが、防御として残す
            return;
        }

        // モーダルタイトルを「○○ さんの音声記録の要約から入力」に動的更新（クライアント名を強調）
        var clientName = getCurrentClientDisplayName();
        var modalTitle = document.getElementById('summaryModalTitle');
        if (modalTitle) {
            if (clientName) {
                modalTitle.innerHTML = '<strong class="text-primary">' + escapeHtml(clientName) + '</strong> さんの音声記録の要約から入力';
            } else {
                modalTitle.textContent = '音声記録の要約から入力';
            }
        }

        loadSummaries(clientId);
        summaryModal.show();
    });

    // 現在選択中のクライアントの表示名を取得（readonly input の data-client-display-name から）
    function getCurrentClientDisplayName() {
        var readonlyInput = document.querySelector('input[data-client-display-name]');
        return readonlyInput ? (readonlyInput.dataset.clientDisplayName || null) : null;
    }

    // 検索フィルタ
    document.getElementById('summarySearch').addEventListener('input', function(e) {
        const filtered = summaries.filter(function(item) {
            return (item.title || item.file_name).toLowerCase().includes(e.target.value.toLowerCase());
        });
        renderSummaries(filtered);
    });

    // 要約一覧を読み込み（client_id でサーバー側フィルタ）
    async function loadSummaries(clientId) {
        const loading = document.getElementById('summaryLoading');
        const list = document.getElementById('summaryList');
        const error = document.getElementById('summaryError');

        loading.classList.remove('d-none');
        list.innerHTML = '';
        error.classList.add('d-none');

        try {
            const url = '/api/audio-records/summaries?client_id=' + encodeURIComponent(clientId);
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) throw new Error('データの取得に失敗しました');

            const data = await response.json();

            if (data.success) {
                summaries = data.data;
                renderSummaries(summaries);
            } else {
                throw new Error('データの取得に失敗しました');
            }
        } catch (err) {
            error.textContent = 'エラー: ' + err.message;
            error.classList.remove('d-none');
        } finally {
            loading.classList.add('d-none');
        }
    }

    // 要約一覧を表示
    function renderSummaries(items) {
        const list = document.getElementById('summaryList');

        if (items.length === 0) {
            list.innerHTML = '<p class="text-muted">要約済みファイルがありません。</p>';
            return;
        }

        list.innerHTML = items.map(function(item) {
            const preview = escapeHtml(item.summary_text).substring(0, 200);
            return '<div class="card mb-2">' +
                '<div class="card-body">' +
                '<h6 class="card-title">' + escapeHtml(item.title || item.file_name) + '</h6>' +
                '<p class="card-text text-muted small">' + formatDate(item.created_at) + '</p>' +
                '<div class="mb-2" style="max-height: 100px; overflow-y: auto; font-size: 0.9em;">' +
                preview + (item.summary_text.length > 200 ? '...' : '') +
                '</div>' +
                '<button type="button" class="btn btn-sm btn-primary btn-insert-summary" data-summary-id="' + item.id + '">' +
                'この要約を追加</button>' +
                '</div></div>';
        }).join('');

        // 挿入ボタンにイベント登録
        list.querySelectorAll('.btn-insert-summary').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = parseInt(this.dataset.summaryId);
                insertSummary(id);
            });
        });
    }

    // 要約をテキストエリアに挿入
    function insertSummary(id) {
        const item = summaries.find(function(s) { return s.id === id; });
        if (item) {
            const textarea = document.getElementById('record_content');
            const current = textarea.value;
            textarea.value = current ? current + '\n\n' + item.summary_text : item.summary_text;
            summaryModal.hide();
        }
    }

    // HTMLエスケープ
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 日時フォーマット
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('ja-JP');
    }
});
</script>
<script>
// 録音画面からの連携: クエリパラメータで渡された情報を自動入力
document.addEventListener('DOMContentLoaded', function() {
    var params = new URLSearchParams(window.location.search);

    // audio_record_id がある場合、要約を取得してトレーニング記録欄に挿入
    var audioRecordId = params.get('audio_record_id');
    if (audioRecordId) {
        fetch('/api/audio-records/' + audioRecordId + '/summary', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.summary_text) {
                var textarea = document.getElementById('record_content');
                if (textarea && !textarea.value) {
                    textarea.value = data.summary_text;
                }
            }
        })
        .catch(function() { /* 要約取得失敗は無視 */ });
    }

    // training_date がある場合、トレーニング日を設定
    var trainingDate = params.get('training_date');
    if (trainingDate) {
        var dateInput = document.getElementById('training_date');
        if (dateInput) dateInput.value = trainingDate;
    }

    // trainer1_id がある場合、担当1を設定
    var trainer1Id = params.get('trainer1_id');
    if (trainer1Id) {
        var trainer1Select = document.getElementById('trainer1_id');
        if (trainer1Select) trainer1Select.value = trainer1Id;
    }

    // trainer2_id がある場合、担当2を設定
    var trainer2Id = params.get('trainer2_id');
    if (trainer2Id) {
        var trainer2Select = document.getElementById('trainer2_id');
        if (trainer2Select) trainer2Select.value = trainer2Id;
    }

});
</script>
@endpush
