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

        {{-- ファイル（複数選択可） --}}
        <div class="mb-3">
            <label for="file" class="form-label">ファイル <span class="text-danger">*</span></label>
            <input type="file" name="file" id="file" multiple
                   class="form-control"
                   accept=".jpg,.jpeg,.png,.heic,.heif,.mp4,.mov">
            {{-- ファイル選択時の事前バリデーションエラー（不適合時のみ表示） --}}
            <div id="fileError" class="text-danger small mt-1 d-none"></div>
            <div class="form-text">
                対応形式:<br>
                写真 jpeg, png, heic（最大20MB）<br>
                動画 mp4, mov（最大1GB）
            </div>

            {{-- 選択ファイル一覧（ファイル選択後に表示。各行に状態バッジ：待機/処理中/完了/失敗） --}}
            <div id="fileList" class="mt-3 d-none">
                <div class="small text-muted mb-1">選択中: <span id="fileListCount">0</span> ファイル</div>
                <ul id="fileListItems" class="list-group list-group-flush border rounded small"></ul>
            </div>
        </div>
    </form>

    {{-- 結果サマリ（全件処理後、失敗があれば表示。手動で一覧へ遷移するための案内） --}}
    <div id="resultSummary" class="d-none mt-3">
        <div class="alert alert-warning mb-2">
            <strong><span id="summaryTotal">0</span> 個中 <span id="summarySuccess">0</span> 個成功、<span id="summaryFailure">0</span> 個失敗。</strong>
            成功したファイルは登録済みです。失敗したファイルは登録されていません。
            <ul id="summaryFailureList" class="mb-0 mt-2"></ul>
        </div>
        <a href="{{ route('media-records.index') }}" class="btn btn-primary">メディア一覧へ</a>
    </div>
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
    const fileListEl = document.getElementById('fileList');
    const fileListCountEl = document.getElementById('fileListCount');
    const fileListItemsEl = document.getElementById('fileListItems');
    const resultSummaryEl = document.getElementById('resultSummary');
    const summaryTotalEl = document.getElementById('summaryTotal');
    const summarySuccessEl = document.getElementById('summarySuccess');
    const summaryFailureEl = document.getElementById('summaryFailure');
    const summaryFailureListEl = document.getElementById('summaryFailureList');

    // 選択中のファイル配列（fileInput.files は live なので、参照を固定する）と
    // 各ファイルに対応する表示行・状態を保持するマップ。
    let selectedFiles = [];
    const fileRowMap = new Map(); // File -> { statusEl, status, error }

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

    // 全ファイルを事前検証し、最初に見つかった NG を返す（案A：1つでも NG なら登録不可）
    function validateAllFiles(files) {
        for (let i = 0; i < files.length; i++) {
            const r = validateFile(files[i]);
            if (!r.ok) {
                return { ok: false, message: '[' + files[i].name + '] ' + r.message };
            }
        }
        return { ok: true };
    }

    // バイト数を人間が読みやすい単位（B/KB/MB/GB）に整形
    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        if (bytes < 1024 * 1024 * 1024) return (bytes / 1024 / 1024).toFixed(1) + ' MB';
        return (bytes / 1024 / 1024 / 1024).toFixed(2) + ' GB';
    }

    // 選択ファイル一覧を画面に描画（各行：名前・種別・サイズ・状態バッジ）
    function buildFileList(files) {
        fileListItemsEl.innerHTML = '';
        fileRowMap.clear();
        files.forEach(function(file) {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center gap-2';

            // 名前 + 種別 + サイズ（左側、長いファイル名は省略）
            const left = document.createElement('div');
            left.className = 'text-truncate';
            left.style.flex = '1 1 auto';
            left.style.minWidth = '0';
            const nameSpan = document.createElement('span');
            nameSpan.className = 'fw-medium';
            nameSpan.textContent = file.name;
            const metaSpan = document.createElement('span');
            metaSpan.className = 'text-muted ms-2';
            const t = classifyFile(file);
            const typeLabel = t === 'photo' ? '写真' : (t === 'video' ? '動画' : '不明');
            metaSpan.textContent = '（' + typeLabel + ', ' + formatSize(file.size) + '）';
            left.appendChild(nameSpan);
            left.appendChild(metaSpan);

            // 状態（右側、待機/処理中/完了/失敗のバッジを後から差し替える）
            const statusEl = document.createElement('div');
            statusEl.className = 'flex-shrink-0 d-flex align-items-center gap-2';
            statusEl.innerHTML = '<span class="badge bg-secondary">待機</span>';

            li.appendChild(left);
            li.appendChild(statusEl);
            fileListItemsEl.appendChild(li);

            fileRowMap.set(file, { statusEl: statusEl, status: 'pending', error: null });
        });
        fileListCountEl.textContent = files.length;
        fileListEl.classList.toggle('d-none', files.length === 0);
    }

    // ファイル行の状態バッジを更新（待機/処理中/完了/失敗）
    function setRowStatus(file, status, errorMessage) {
        const row = fileRowMap.get(file);
        if (!row) return;
        row.status = status;
        row.error = errorMessage || null;
        let html = '';
        switch (status) {
            case 'pending':
                html = '<span class="badge bg-secondary">待機</span>';
                break;
            case 'processing':
                html = '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>'
                     + '<span class="small">処理中</span>';
                break;
            case 'done':
                html = '<span class="badge bg-success">完了</span>';
                break;
            case 'error':
                html = '<span class="badge bg-danger">失敗</span>';
                break;
        }
        row.statusEl.innerHTML = html;
        // 失敗時は理由を行の下に小さく追記する（バッジ右ではスペース不足なので別 div で）
        if (status === 'error' && errorMessage) {
            const li = row.statusEl.closest('li');
            // 既存の理由メモがあれば置き換える
            const old = li.querySelector('.row-error-message');
            if (old) old.remove();
            const msg = document.createElement('div');
            msg.className = 'row-error-message text-danger small w-100';
            msg.textContent = errorMessage;
            // li を flex-wrap させて新行に表示
            li.classList.add('flex-wrap');
            li.appendChild(msg);
        }
    }

    // ファイル選択時：選択ファイル配列の更新・一覧描画・事前バリデーション
    fileInput.addEventListener('change', function() {
        fileErrorEl.classList.add('d-none');
        fileErrorEl.textContent = '';
        fileInput.classList.remove('is-invalid');
        // 前回登録結果のサマリは新たな選択で隠す
        resultSummaryEl.classList.add('d-none');

        selectedFiles = Array.from(this.files);
        buildFileList(selectedFiles);
        if (selectedFiles.length === 0) return;

        const result = validateAllFiles(selectedFiles);
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

    // 全体進捗の context（N / M / filename）。setPhase で文言に組み込むため、
    // フェーズが変わっても引き継げるようスコープ変数で保持する。
    let currentProgressContext = { idx: 0, total: 0, filename: '' };

    // フェーズ別の進捗メッセージとバー表現を切り替える。
    //   uploading: % 付きバー（XHR progress で setProgress が 0→100 を動かす）
    //   converting / thumbnail: バー満幅 + % テキスト空。progress-bar-striped+animated が
    //     HTML 側で常時付与されているため、テキストを空にしておくだけで縞だけが流れて
    //     「処理中・止まっていない」が伝わる（変換の実進捗% はフロントから取得できないため、
    //     正確な % を出すには変換の非同期化＋ポーリングが必要で大掛かりすぎる。将来課題）。
    function setPhase(phase) {
        const ctx = currentProgressContext;
        const prefix = (ctx.idx + 1) + ' / ' + ctx.total + ' 個目: ' + ctx.filename;
        let suffix;
        if (phase === 'converting') {
            suffix = ' を変換中…';
        } else if (phase === 'thumbnail') {
            suffix = ' のサムネイル生成中…';
        } else {
            suffix = ' をアップロード中…';
        }
        progressMessage.textContent = prefix + suffix + ' 画面を閉じないでください。';

        if (phase === 'uploading') {
            setProgress(0);
        } else {
            progressBar.style.width = '100%';
            progressBar.textContent = '';
            progressBar.setAttribute('aria-valuenow', '100');
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

    // 1 ファイル分の処理（upload-url → 直PUT → store → convert? → generate-thumbnail?）。
    // 既存の単一登録ロジックをそのまま 1 ファイル単位の関数に切り出したもの。
    // 例外を throw すれば呼び出し側のループで catch して「失敗」扱いとし、続行する。
    async function processOneFile(file, clientId) {
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

        // ⑤サムネイル生成（写真は heic/jpeg/png 原本から、動画は mov/mp4 原本から 1 秒目フレーム）
        if (media.thumbnail_status === 'pending' && (media.type === 'photo' || media.type === 'video')) {
            setPhase('thumbnail');
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
    }

    // 全体進捗（N 個中 M 個目）と現在処理中のファイル名を context にセットし、
    // アップロードフェーズで進捗エリアを初期化する（setPhase 内で setProgress(0) も実行）。
    function setOverallProgress(currentIdx, total, filename) {
        currentProgressContext = { idx: currentIdx, total: total, filename: filename };
        setPhase('uploading');
    }

    // 全件処理後、失敗があった場合の結果サマリを描画。成功分は登録済みのまま残し、
    // 失敗ファイル名と理由を列挙して「メディア一覧へ」ボタンで手動遷移してもらう。
    function showResultSummary(results) {
        const successCount = results.filter(function(r) { return r.ok; }).length;
        const failureCount = results.length - successCount;
        const failures = results.filter(function(r) { return !r.ok; });

        summaryTotalEl.textContent = String(results.length);
        summarySuccessEl.textContent = String(successCount);
        summaryFailureEl.textContent = String(failureCount);
        summaryFailureListEl.innerHTML = '';
        failures.forEach(function(r) {
            const li = document.createElement('li');
            li.textContent = r.file.name + ' — ' + r.error;
            summaryFailureListEl.appendChild(li);
        });
        resultSummaryEl.classList.remove('d-none');
        // 進捗エリアは隠す（処理は完了している）
        inProgressEl.classList.add('d-none');
    }

    submitBtn.addEventListener('click', async function() {
        const clientId = clientSelect.value;
        const files = selectedFiles;

        // 押下時の素朴なガード（disable はしない方針。再チェック + alert で弾く）
        if (!clientId) { alert('クライアントを選択してください。'); return; }
        if (files.length === 0) { alert('ファイルを選択してください。'); return; }
        const v = validateAllFiles(files);
        if (!v.ok) { alert(v.message); return; }

        // 前回の結果サマリが残っていれば隠し、全行を待機状態に戻す
        resultSummaryEl.classList.add('d-none');
        files.forEach(function(f) { setRowStatus(f, 'pending'); });
        // 失敗時の理由メモも除去
        fileListItemsEl.querySelectorAll('.row-error-message').forEach(function(el) { el.remove(); });
        fileListItemsEl.querySelectorAll('li.flex-wrap').forEach(function(el) { el.classList.remove('flex-wrap'); });

        setState('uploading');
        const results = [];
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            setOverallProgress(i, files.length, file.name);
            setRowStatus(file, 'processing');
            try {
                await processOneFile(file, clientId);
                setRowStatus(file, 'done');
                results.push({ file: file, ok: true });
            } catch (e) {
                console.error(e);
                const msg = e.message || '登録に失敗しました。';
                setRowStatus(file, 'error', msg);
                results.push({ file: file, ok: false, error: msg });
                // ループは継続（次のファイルへ進む）
            }
        }

        // 全件処理後：全成功なら一覧へ自動遷移、失敗が1つでもあれば結果サマリを出して手動遷移
        const failureCount = results.filter(function(r) { return !r.ok; }).length;
        if (failureCount === 0) {
            window.location.href = '{{ route("media-records.index") }}';
        } else {
            showResultSummary(results);
            // disable は保持（結果サマリの「一覧へ」ボタンで遷移する設計）
        }
    });
});
</script>
@endpush
