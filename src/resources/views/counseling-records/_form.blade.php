{{-- 相談記録登録・編集共通フォーム（クライアントは常に固定モード） --}}
<form method="POST" action="{{ $action }}" id="counselingRecordForm" novalidate>
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

                {{-- 相談日 --}}
                <div class="col-md-3">
                    <label for="consultation_date" class="form-label">相談日 <span class="text-danger">*</span></label>
                    <input type="text" name="consultation_date" id="consultation_date"
                        class="form-control datepicker @error('consultation_date') is-invalid @enderror"
                        value="{{ old('consultation_date', $record?->consultation_date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                        placeholder="例: 2026-04-01" pattern="\d{4}-\d{2}-\d{2}" maxlength="10" required>
                    @error('consultation_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- 相談時間 --}}
                <div class="col-md-3">
                    <label for="consultation_time" class="form-label">相談時刻</label>
                    <input type="time" name="consultation_time" id="consultation_time"
                        class="form-control @error('consultation_time') is-invalid @enderror"
                        value="{{ old('consultation_time', $record?->consultation_time ? substr($record->consultation_time, 0, 5) : '') }}">
                    @error('consultation_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- 参加者 --}}
                <div class="col-md-12">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <label class="form-label mb-0">参加者 <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-participant">追加</button>
                    </div>
                    <div id="participants-container">
                        @php
                            $oldParticipants = old('participants', $participants instanceof \Illuminate\Support\Collection ? $participants->toArray() : $participants);
                        @endphp
                        @forelse($oldParticipants as $index => $participant)
                            <div class="row g-3 mb-2 participant-row">
                                <div class="col-md-3">
                                    <select name="participants[{{ $index }}][participant_type]" class="form-select">
                                        <option value="">本人との関係を選択</option>
                                        @foreach(['本人', '支援者', '母', '父', '配偶者', 'きょうだい', '子', '祖父母', 'その他'] as $type)
                                            <option value="{{ $type }}"
                                                {{ ($participant['participant_type'] ?? '') === $type ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" name="participants[{{ $index }}][participant_detail]"
                                        class="form-control"
                                        inputmode="text" value="{{ $participant['participant_detail'] ?? '' }}"
                                        maxlength="255" placeholder="関係の詳細">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger remove-participant">削除</button>
                                </div>
                            </div>
                        @empty
                            <div class="row g-3 mb-2 participant-row">
                                <div class="col-md-3">
                                    <select name="participants[0][participant_type]" class="form-select">
                                        <option value="">本人との関係を選択</option>
                                        @foreach(['本人', '支援者', '母', '父', '配偶者', 'きょうだい', '子', '祖父母', 'その他'] as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" name="participants[0][participant_detail]"
                                        class="form-control"
                                        inputmode="text" maxlength="255" placeholder="関係の詳細">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger remove-participant">削除</button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                    @if($errors->has('participants') || $errors->has('participants.*') || $errors->has('participants.*.participant_type') || $errors->has('participants.*.participant_detail'))
                        <div class="text-danger small mt-1">
                            @foreach($errors->get('participants.*') as $key => $messages)
                                @foreach($messages as $message)
                                    {{ $message }}<br>
                                @endforeach
                            @endforeach
                        </div>
                    @endif
                    <p class="text-muted small mt-1 mb-0">例:「支援者」「〇〇病院 ケースワーカー佐藤さん」、「母」「山田花子」</p>
                </div>

                {{-- 担当1 --}}
                <div class="col-md-4">
                    <label for="counselor1_id" class="form-label">担当1 <span class="text-danger">*</span></label>
                    <select name="counselor1_id" id="counselor1_id" class="form-select @error('counselor1_id') is-invalid @enderror" required>
                        <option value="">選択してください</option>
                        @foreach($counselors as $counselor)
                            <option value="{{ $counselor->id }}"
                                {{ old('counselor1_id', $record?->counselor1_id ?? auth()->id()) == $counselor->id ? 'selected' : '' }}>
                                {{ $counselor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('counselor1_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- 担当2 --}}
                <div class="col-md-4">
                    <label for="counselor2_id" class="form-label">担当2</label>
                    <select name="counselor2_id" id="counselor2_id" class="form-select @error('counselor2_id') is-invalid @enderror">
                        <option value="">なし</option>
                        @foreach($counselors as $counselor)
                            <option value="{{ $counselor->id }}"
                                {{ old('counselor2_id', $record?->counselor2_id) == $counselor->id ? 'selected' : '' }}>
                                {{ $counselor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('counselor2_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="row g-3 mt-1">
                {{-- 参加状況 --}}
                <div class="col-md-4">
                    <label for="attendance" class="form-label">参加状況 <span class="text-danger">*</span></label>
                    <select name="attendance" id="attendance" class="form-select @error('attendance') is-invalid @enderror" required>
                        <option value="">選択してください</option>
                        @foreach(['参加', 'キャンセル（連絡あり）', 'キャンセル（連絡なし）'] as $option)
                            <option value="{{ $option }}"
                                {{ old('attendance', $record?->attendance) === $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                    @error('attendance')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- 参加形態 --}}
                <div class="col-md-4">
                    <label for="consultation_format" class="form-label">参加形態 <span class="text-danger">*</span></label>
                    <select name="consultation_format" id="consultation_format" class="form-select @error('consultation_format') is-invalid @enderror" required>
                        <option value="">選択してください</option>
                        @foreach(['対面', 'ビデオ通話', '電話', 'メール', '同行', '訪問', 'その他'] as $option)
                            <option value="{{ $option }}"
                                {{ old('consultation_format', $record?->consultation_format) === $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                    @error('consultation_format')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- 参加形態（詳細） --}}
                <div class="col-md-4">
                    <label for="consultation_format_detail" class="form-label">参加形態（詳細）</label>
                    <input type="text" name="consultation_format_detail" id="consultation_format_detail"
                        class="form-control @error('consultation_format_detail') is-invalid @enderror"
                        inputmode="text" value="{{ old('consultation_format_detail', $record?->consultation_format_detail) }}"
                        maxlength="255" placeholder="「その他」の場合に入力">
                    @error('consultation_format_detail')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- 相談内容 --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">相談内容</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                {{-- インテーク・フォローアップ --}}
                <div class="col-md-12">
                    <div class="form-check form-check-inline">
                        <input type="hidden" name="is_intake" value="0">
                        <input class="form-check-input" type="checkbox" name="is_intake" id="is_intake" value="1"
                            {{ old('is_intake', $record?->is_intake) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_intake">インテーク</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="hidden" name="is_followup" value="0">
                        <input class="form-check-input" type="checkbox" name="is_followup" id="is_followup" value="1"
                            {{ old('is_followup', $record?->is_followup) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_followup">フォローアップ</label>
                    </div>
                </div>

                {{-- 相談内容 --}}
                <div class="col-md-4">
                    <label for="consultation_type_id" class="form-label">相談内容</label>
                    <select name="consultation_type_id" id="consultation_type_id" class="form-select @error('consultation_type_id') is-invalid @enderror">
                        <option value="">選択してください</option>
                        @foreach($consultationTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ old('consultation_type_id', $record?->consultation_type_id) == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('consultation_type_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- 相談詳細 --}}
                <div class="col-md-8">
                    <label for="consultation_detail" class="form-label">相談内容（詳細）</label>
                    <input type="text" name="consultation_detail" id="consultation_detail"
                        class="form-control @error('consultation_detail') is-invalid @enderror"
                        inputmode="text" value="{{ old('consultation_detail', $record?->consultation_detail) }}"
                        maxlength="255" placeholder="主旨">
                    @error('consultation_detail')
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

    {{-- 相談記録 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">相談記録 <span class="text-muted">（事実を客観的に記録）</span></h6>
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
            <h6 class="mb-0">所感 <span class="text-muted">（カウンセラー間共有・クライアント非開示）</span></h6>
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
            <a href="{{ $selectedClientId ? route('clients.show', $selectedClientId) : route('counseling-records.index') }}" class="btn btn-secondary js-leave-link">キャンセル</a>
        @endif
        <button type="submit" class="btn btn-success">{{ $record ? '更新' : '登録' }}</button>
    </div>
</form>

{{-- 参加者の動的追加JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('participants-container');
    const addBtn = document.getElementById('add-participant');

    // 参加者行の追加
    addBtn.addEventListener('click', function() {
        const rows = container.querySelectorAll('.participant-row');
        const index = rows.length;
        const row = document.createElement('div');
        row.className = 'row g-3 mb-2 participant-row';
        row.innerHTML = `
            <div class="col-md-3">
                <select name="participants[${index}][participant_type]" class="form-select">
                    <option value="">本人との関係を選択</option>
                    <option value="本人">本人</option>
                    <option value="支援者">支援者</option>
                    <option value="母">母</option>
                    <option value="父">父</option>
                    <option value="配偶者">配偶者</option>
                    <option value="きょうだい">きょうだい</option>
                    <option value="子">子</option>
                    <option value="祖父母">祖父母</option>
                    <option value="その他">その他</option>
                </select>
            </div>
            <div class="col-md-7">
                <input type="text" name="participants[${index}][participant_detail]"
                    class="form-control"
                    inputmode="text" maxlength="255" placeholder="関係の詳細">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger remove-participant">削除</button>
            </div>
        `;
        container.appendChild(row);
    });

    // フォーム送信時に必須チェックを実行（上部・下部ボタン共通）
    const form = document.getElementById('counselingRecordForm');
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

        // 2. 相談日
        checkRequired('consultation_date', '相談日を入力してください。');

        // 3. 参加者（最低1人は区分を選択していること）
        var participantSelects = container.querySelectorAll('.participant-row select');
        var hasParticipant = false;
        participantSelects.forEach(function(select) {
            if (select.value) hasParticipant = true;
        });
        var participantsContainer = document.getElementById('participants-container');
        var existingParticipantAlert = participantsContainer.parentElement.querySelector('.participant-error');
        if (existingParticipantAlert) existingParticipantAlert.remove();
        if (!hasParticipant) {
            var participantAlert = document.createElement('div');
            participantAlert.className = 'text-danger small mt-1 participant-error';
            participantAlert.textContent = '参加者を1人以上設定してください。';
            participantsContainer.after(participantAlert);
            errors.push('参加者を1人以上設定してください。');
            if (!firstInvalidElement) firstInvalidElement = participantSelects[0];
        }

        // 4. 担当1
        checkRequired('counselor1_id', '担当1を選択してください。');

        // 5. 参加状況
        checkRequired('attendance', '参加状況を選択してください。');

        // 6. 参加形態
        checkRequired('consultation_format', '参加形態を選択してください。');

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

    // 参加者行の削除（イベント委任）
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-participant')) {
            const row = e.target.closest('.participant-row');
            // 最低1行は残す
            if (container.querySelectorAll('.participant-row').length > 1) {
                row.remove();
            } else {
                // 最後の1行はクリアのみ
                row.querySelector('select').value = '';
                row.querySelector('input').value = '';
            }
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

    // audio_record_id がある場合、要約を取得して相談記録欄に挿入
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

    // consultation_date がある場合、相談日を設定
    var consultationDate = params.get('consultation_date');
    if (consultationDate) {
        var dateInput = document.getElementById('consultation_date');
        if (dateInput) dateInput.value = consultationDate;
    }

    // counselor1_id がある場合、担当1を設定
    var counselor1Id = params.get('counselor1_id');
    if (counselor1Id) {
        var counselor1Select = document.getElementById('counselor1_id');
        if (counselor1Select) counselor1Select.value = counselor1Id;
    }

    // counselor2_id がある場合、担当2を設定
    var counselor2Id = params.get('counselor2_id');
    if (counselor2Id) {
        var counselor2Select = document.getElementById('counselor2_id');
        if (counselor2Select) counselor2Select.value = counselor2Id;
    }

    // participants_data がある場合、参加者行を復元
    var participantsDataJson = params.get('participants_data');
    if (participantsDataJson) {
        try {
            var participantsData = JSON.parse(participantsDataJson);
            var container = document.getElementById('participants-container');
            var existingRows = container.querySelectorAll('.participant-row');

            participantsData.forEach(function(p, i) {
                // 既存行があればそこに入力、なければ追加ボタンで行を増やす
                if (i < existingRows.length) {
                    var row = existingRows[i];
                    if (p.participant_type) row.querySelector('select').value = p.participant_type;
                    if (p.participant_detail) row.querySelector('input').value = p.participant_detail;
                } else {
                    document.getElementById('add-participant').click();
                    var newRows = container.querySelectorAll('.participant-row');
                    var newRow = newRows[newRows.length - 1];
                    if (p.participant_type) newRow.querySelector('select').value = p.participant_type;
                    if (p.participant_detail) newRow.querySelector('input').value = p.participant_detail;
                }
            });
        } catch (e) { /* 復元失敗は無視 */ }
    }
});
</script>
@endpush
