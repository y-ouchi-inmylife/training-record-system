@extends('layouts.app')

@section('title', 'メディア登録（ファイルのアップロード）')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">メディア登録（ファイルのアップロード）</h2>
        <div class="d-flex gap-2">
            {{-- 設計上のキャンセル遷移先は S-1302。未実装のため当面はダッシュボードへ戻す --}}
            <a href="{{ route('dashboard') }}" class="btn btn-secondary" id="cancelBtn">キャンセル</a>
            <button type="button" class="btn btn-success" id="submitBtn">登録</button>
        </div>
    </div>

    {{-- 登録完了メッセージ・作成内容（reality check用）。フォームリセット後も残す --}}
    <div id="uploadResult" class="d-none"></div>

    {{-- アップロード中表示 --}}
    <div id="uploadInProgress" class="alert alert-warning d-none">
        アップロード中… 画面を閉じないでください。
    </div>

    <form id="mediaUploadForm" onsubmit="return false;">
        {{-- クライアント --}}
        <div class="mb-3">
            <label for="media_client_id" class="form-label">
                クライアント <span class="text-danger">*</span>
            </label>
            <select name="client_id" id="media_client_id" class="form-select select2-client-media">
                <option value="">クライアントを検索...</option>
            </select>
        </div>

        {{-- ファイル --}}
        <div class="mb-3">
            <label for="file" class="form-label">ファイル <span class="text-danger">*</span></label>
            <input type="file" name="file" id="file"
                   class="form-control"
                   accept=".jpg,.jpeg,.png,.heic,.heif,.mp4,.mov">
            <div class="form-text">
                対応形式:<br>
                写真 jpeg, png, heic（最大20MB）<br>
                動画 mp4, mov（最大1GB）
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // クライアント検索Select2（既存の音声記録アップロード画面と同じ作法）
    $('.select2-client-media').select2({
        theme: 'bootstrap-5',
        placeholder: 'クライアントを検索（内部ID、名前、かな）',
        allowClear: true,
        width: '100%',
        ajax: {
            url: '/api/clients/search',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        },
        minimumInputLength: 1,
        language: {
            inputTooShort: function () { return '1文字以上入力してください'; },
            noResults: function () { return '該当するクライアントが見つかりません'; },
            searching: function () { return '検索中...'; }
        }
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const submitBtn = document.getElementById('submitBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const form = document.getElementById('mediaUploadForm');
    const fileInput = document.getElementById('file');
    const clientSelect = document.getElementById('media_client_id');
    const resultEl = document.getElementById('uploadResult');
    const inProgressEl = document.getElementById('uploadInProgress');

    // アップロード中の入力不可制御
    function setBusy(busy) {
        submitBtn.disabled = busy;
        cancelBtn.classList.toggle('disabled', busy);
        cancelBtn.setAttribute('aria-disabled', busy ? 'true' : 'false');
        fileInput.disabled = busy;
        $(clientSelect).prop('disabled', busy);
        inProgressEl.classList.toggle('d-none', !busy);
    }

    // 422等のエラーレスポンスからユーザー向けメッセージを組み立てる
    async function readErrorMessage(res, fallback) {
        try {
            const body = await res.json();
            // Laravel標準422: { message, errors: { field: [msg] } }
            if (body && body.errors) {
                return Object.values(body.errors).flat().join('\n');
            }
            // 自前422: { error: { field: [msg] } }
            if (body && body.error) {
                return Object.values(body.error).flat().join('\n');
            }
            if (body && body.message) { return body.message; }
        } catch (e) { /* 本文がJSONでない場合のフォールバックは下へ */ }
        return fallback;
    }

    // 完了メッセージと作成情報を表示（reset後も残す）
    function showResult(media) {
        const typeLabel = media.type === 'photo' ? '写真' : (media.type === 'video' ? '動画' : media.type);
        // XSS回避のため値はtextContentで投入
        resultEl.className = 'alert alert-success';
        resultEl.textContent = '';
        const strong = document.createElement('strong');
        strong.textContent = '登録完了';
        resultEl.appendChild(strong);
        const detail = document.createElement('div');
        detail.className = 'mt-2 small';
        const rows = [
            ['ID', media.id],
            ['種別', typeLabel],
            ['元ファイル名', media.original_filename || ''],
            ['表示名', media.title || media.original_filename || ''],
            ['クライアントID', media.client_id != null ? String(media.client_id) : ''],
            ['file_path', media.file_path || ''],
        ];
        rows.forEach(function(r) {
            const line = document.createElement('div');
            line.textContent = r[0] + ': ' + r[1];
            detail.appendChild(line);
        });
        resultEl.appendChild(detail);
        resultEl.classList.remove('d-none');
    }

    submitBtn.addEventListener('click', async function() {
        const clientId = clientSelect.value;
        const file = fileInput.files[0];

        // 素朴なガード（形式・サイズの事前検証は次フェーズ）
        if (!clientId) { alert('クライアントを選択してください。'); return; }
        if (!file) { alert('ファイルを選択してください。'); return; }

        setBusy(true);
        try {
            // ①署名付きURL発行（自アプリ宛 → CSRF必要）
            const uploadUrlRes = await fetch('/api/media-records/upload-url', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    original_filename: file.name,
                    mime_type: file.type,
                    file_size: file.size,
                }),
            });
            if (!uploadUrlRes.ok) {
                throw new Error(await readErrorMessage(uploadUrlRes, '署名URLの発行に失敗しました。'));
            }
            const uploadUrlBody = await uploadUrlRes.json();
            const uploadUrl = uploadUrlBody.data.upload_url;
            const storageKey = uploadUrlBody.data.storage_key;

            // ②さくらストレージへの直PUT（別オリジン → CSRF・Cookie不要）
            const putRes = await fetch(uploadUrl, {
                method: 'PUT',
                credentials: 'omit',
                headers: { 'Content-Type': file.type || 'application/octet-stream' },
                body: file,
            });
            if (!putRes.ok) {
                throw new Error('ストレージへのアップロードに失敗しました（HTTP ' + putRes.status + '）。');
            }

            // ③メディアレコード作成（自アプリ宛 → CSRF必要）
            const storeRes = await fetch('/api/media-records', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    client_id: clientId,
                    storage_key: storageKey,
                    original_filename: file.name,
                    mime_type: file.type,
                    file_size: file.size,
                }),
            });
            if (!storeRes.ok) {
                throw new Error(await readErrorMessage(storeRes, 'メディアレコードの作成に失敗しました。'));
            }
            const storeBody = await storeRes.json();

            // 完了表示 → 次の登録に備えてフォームのみリセット（完了メッセージは残す）
            showResult(storeBody.data);
            form.reset();
            $(clientSelect).val(null).trigger('change');
        } catch (e) {
            console.error(e);
            alert(e.message || '登録に失敗しました。');
        } finally {
            setBusy(false);
        }
    });
});
</script>
@endpush
