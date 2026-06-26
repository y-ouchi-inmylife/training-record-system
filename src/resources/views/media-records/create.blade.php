@extends('layouts.app')

@section('title', 'メディア登録（ファイルのアップロード）')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">メディア登録（ファイルのアップロード）</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('media-records.index') }}" class="btn btn-secondary" id="cancelBtn">キャンセル</a>
            <button type="button" class="btn btn-success" id="submitBtn">登録</button>
        </div>
    </div>

    {{-- アップロード中表示（テキスト＋進捗バー） --}}
    <div id="uploadInProgress" class="d-none mb-3">
        <div id="progressMessage" class="alert alert-warning mb-2">
            アップロード中… 画面を閉じないでください。
        </div>
        <div class="progress" style="height: 24px;">
            <div id="progressBar"
                 class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar"
                 style="width: 0%"
                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>
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
            {{-- ファイル選択時の事前バリデーションエラー（不適合時のみ表示） --}}
            <div id="fileError" class="text-danger small mt-1 d-none"></div>
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

    // サーバ側定数を Single Source of Truth として渡す（クライアント側に値を二重に持たない）
    const PHOTO_MIMES = @json(\App\Models\MediaRecord::PHOTO_MIME_TYPES);
    const VIDEO_MIMES = @json(\App\Models\MediaRecord::VIDEO_MIME_TYPES);
    const PHOTO_EXTS = @json(\App\Models\MediaRecord::PHOTO_EXTENSIONS);
    const VIDEO_EXTS = @json(\App\Models\MediaRecord::VIDEO_EXTENSIONS);
    const MAX_PHOTO_SIZE = @json(\App\Models\MediaRecord::MAX_PHOTO_SIZE);
    const MAX_VIDEO_SIZE = @json(\App\Models\MediaRecord::MAX_VIDEO_SIZE);

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const submitBtn = document.getElementById('submitBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const fileInput = document.getElementById('file');
    const clientSelect = document.getElementById('media_client_id');
    const fileErrorEl = document.getElementById('fileError');
    const inProgressEl = document.getElementById('uploadInProgress');
    const progressMessage = document.getElementById('progressMessage');
    const progressBar = document.getElementById('progressBar');

    // ファイルを写真／動画に分類（拡張子と MIME の OR 判定で heic file.type 問題に対応）
    function classifyFile(file) {
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const mime = file.type;
        if (PHOTO_MIMES.includes(mime) || PHOTO_EXTS.includes(ext)) return 'photo';
        if (VIDEO_MIMES.includes(mime) || VIDEO_EXTS.includes(ext)) return 'video';
        return null;
    }

    // ファイルの形式・サイズを検証
    function validateFile(file) {
        const type = classifyFile(file);
        if (type === null) {
            return { ok: false, message: '対応形式は写真(jpeg/png/heic)・動画(mp4/mov)のみです。' };
        }
        if (type === 'photo' && file.size > MAX_PHOTO_SIZE) {
            return { ok: false, message: '写真は20MB以下にしてください。' };
        }
        if (type === 'video' && file.size > MAX_VIDEO_SIZE) {
            return { ok: false, message: '動画は1GB以下にしてください。' };
        }
        return { ok: true };
    }

    // ファイル選択時の事前バリデーション
    fileInput.addEventListener('change', function() {
        fileErrorEl.classList.add('d-none');
        fileErrorEl.textContent = '';
        fileInput.classList.remove('is-invalid');

        const file = this.files[0];
        if (!file) return;

        const result = validateFile(file);
        if (!result.ok) {
            fileErrorEl.textContent = result.message;
            fileErrorEl.classList.remove('d-none');
            fileInput.classList.add('is-invalid');
        }
    });

    // 状態遷移（idle / uploading）。完了は遷移、エラーは idle に戻す。
    function setState(state) {
        const busy = (state === 'uploading');
        submitBtn.disabled = busy;
        cancelBtn.classList.toggle('disabled', busy);
        cancelBtn.setAttribute('aria-disabled', busy ? 'true' : 'false');
        fileInput.disabled = busy;
        $(clientSelect).prop('disabled', busy);
        inProgressEl.classList.toggle('d-none', !busy);
        if (!busy) setProgress(0);
    }

    function setProgress(pct) {
        progressBar.style.width = pct + '%';
        progressBar.textContent = pct + '%';
        progressBar.setAttribute('aria-valuenow', String(pct));
    }

    // フェーズ別の進捗メッセージ切り替え（uploading → converting）。
    // converting は変換完了まで時間がかかるため、進捗バーは100%縞アニメで「処理中」を示す。
    function setPhase(phase) {
        if (phase === 'converting') {
            progressMessage.textContent = '変換中… 画面を閉じないでください。';
            setProgress(100);
        } else {
            progressMessage.textContent = 'アップロード中… 画面を閉じないでください。';
        }
    }

    // 422等のエラーレスポンスからユーザー向けメッセージを組み立てる
    async function readErrorMessage(res, fallback) {
        try {
            const body = await res.json();
            if (body && body.errors) {
                return Object.values(body.errors).flat().join('\n');
            }
            if (body && body.error) {
                return Object.values(body.error).flat().join('\n');
            }
            if (body && body.message) { return body.message; }
        } catch (e) { /* ignore */ }
        return fallback;
    }

    // 直PUT（XHR + 進捗イベント）。別オリジン → CSRF・Cookie 不要、Promise でラップ。
    function putWithProgress(url, file) {
        return new Promise(function(resolve, reject) {
            const xhr = new XMLHttpRequest();
            xhr.open('PUT', url);
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    setProgress(Math.round((e.loaded / e.total) * 100));
                }
            });
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    setProgress(100);
                    resolve();
                } else {
                    reject(new Error('ストレージへのアップロードに失敗しました（HTTP ' + xhr.status + '）'));
                }
            };
            xhr.onerror = function() {
                reject(new Error('ストレージへの通信に失敗しました。'));
            };
            xhr.setRequestHeader('Content-Type', file.type || 'application/octet-stream');
            xhr.send(file);
        });
    }

    submitBtn.addEventListener('click', async function() {
        const clientId = clientSelect.value;
        const file = fileInput.files[0];

        // 押下時の素朴なガード（disable はしない方針。再チェック + alert で弾く）
        if (!clientId) { alert('クライアントを選択してください。'); return; }
        if (!file) { alert('ファイルを選択してください。'); return; }
        const v = validateFile(file);
        if (!v.ok) { alert(v.message); return; }

        setState('uploading');
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

            // ②さくらストレージへの直PUT（XHRで進捗取得、別オリジン → CSRF・Cookie不要）
            await putWithProgress(uploadUrl, file);

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

            // ④表示用変換（写真の heic/heif → jpeg、動画の mov → mp4）
            //   store のレスポンスで conversion_status=pending（photo/video のどちらか）の場合に convert を呼ぶ。
            //   not_required（jpeg/png/mp4）は呼ばない。
            const storeBody = await storeRes.json();
            const media = storeBody.data || {};
            if (media.conversion_status === 'pending' && (media.type === 'photo' || media.type === 'video')) {
                setPhase('converting');
                const convertRes = await fetch('/api/media-records/' + encodeURIComponent(media.id) + '/convert', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                if (!convertRes.ok) {
                    throw new Error(await readErrorMessage(convertRes, '表示用変換に失敗しました。'));
                }
            }

            // ⑤サムネイル生成（3b-1 は写真のみ。動画は3b-2でこの条件を photo/video に拡張する）
            //   全メディア（jpeg/png/mp4/heic/mov）が thumbnail_status=pending で store されるが、
            //   3b-1 ではジョブが photo のみ処理するため、フロント側で type=photo に絞って呼ぶ。
            //   失敗時はアラート → idle 復帰（既存 convert と同じ挙動）。
            if (media.thumbnail_status === 'pending' && media.type === 'photo') {
                setPhase('converting');
                const thumbRes = await fetch('/api/media-records/' + encodeURIComponent(media.id) + '/generate-thumbnail', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                if (!thumbRes.ok) {
                    throw new Error(await readErrorMessage(thumbRes, 'サムネイル生成に失敗しました。'));
                }
            }

            // 完了 → S-1302（メディア一覧）へ遷移（設計の完了状態）
            window.location.href = '{{ route("media-records.index") }}';
        } catch (e) {
            console.error(e);
            // エラー → 入力中（idle）へ復帰
            setState('idle');
            alert(e.message || '登録に失敗しました。');
        }
    });
});
</script>
@endpush
