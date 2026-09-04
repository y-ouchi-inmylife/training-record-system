@extends('layouts.app')

@section('title', 'クライアント詳細')

@push('styles')
<style>
/* トレーニング記録の一覧領域: ビューポート基準の最大高さ、
   収まらない場合のみ縦スクロール */
.training-records-scroll {
    max-height: 60vh;
    overflow-y: auto;
}

/* トレーニング記録タイムライン */
.training-records-timeline {
    position: relative;
    padding-left: 24px;
}
.training-records-timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}
.record-block-link {
    display: block;
    text-decoration: none;
    color: inherit;
    margin-bottom: 1rem;
}
.record-block-link:hover .record-block {
    background: #f8f9fa;
}
.record-block-link:focus-visible {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
    border-radius: 4px;
}
.record-block {
    position: relative;
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: #fff;
}
/* 点マーカー: 縦線上の位置に配置 */
.record-block::before {
    content: '';
    position: absolute;
    left: -18px;
    top: 16px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--bs-primary);
}
.record-block__line1 {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    color: #212529;
}
.record-block__line2,
.record-block__line3 {
    color: #495057;
    margin-top: 4px;
    font-size: 0.9em;
}
.record-block__line3 {
    white-space: pre-wrap;
    word-break: break-word;
}
</style>
@endpush

@section('content')
<div class="container">
    {{-- ヘッダーサマリー --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-start">
            {{-- 左: 氏名エリア --}}
            <div class="flex-grow-1 me-3">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <h2 class="mb-0">
                        {{ $client->full_name }}@if($client->full_name_kana)<span class="text-muted fs-6">（{{ $client->full_name_kana }}）</span>@endif
                    </h2>
                    {{-- 閲覧状態バッジ（現行の4分岐ロジックを移植） --}}
                    @if(!$client->is_viewable && empty($client->email))
                        <span class="badge bg-secondary fs-6">メールアドレス未登録</span>
                    @elseif(!$client->is_viewable)
                        <span class="badge bg-secondary fs-6">未解放</span>
                    @elseif(empty($client->password))
                        <span class="badge bg-warning text-dark fs-6">解放中（パスワード未設定）</span>
                    @else
                        <span class="badge bg-success fs-6">解放中</span>
                    @endif
                    {{-- 閲覧解放 / 解放取り消しボタン（旧・基本情報カードヘッダーから移動） --}}
                    @if(!$client->is_viewable && $client->email)
                        <form method="POST" action="{{ route('client-view-release.store', $client) }}"
                              onsubmit="return confirmReleaseView()" class="d-inline m-0">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">閲覧を解放する</button>
                        </form>
                    @elseif($client->is_viewable)
                        <form method="POST" action="{{ route('client-view-revoke.store', $client) }}"
                              onsubmit="return confirmRevokeView()" class="d-inline m-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">閲覧の解放を取り消す</button>
                        </form>
                    @endif
                    <div class="d-flex align-items-baseline gap-2 ms-3">
                        <span class="text-muted small">内部ID</span>
                        <span class="font-monospace fs-5">{{ $client->internal_id }}</span>
                    </div>
                </div>
            </div>
            {{-- 右: ボタン群 --}}
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">&laquo; クライアント一覧に戻る</a>
                @if(!$activeIntakeToken)
                    <button type="button" class="btn btn-outline-primary"
                            data-bs-toggle="modal" data-bs-target="#issueIntakeTokenModal">
                        URL発行
                    </button>
                @endif
                <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary">編集</a>
                @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="d-inline"
                          onsubmit="return confirmDelete()">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">削除</button>
                    </form>
                    <script>
                    function confirmDelete() {
                        @if($client->trainingRecords->count() > 0)
                            alert('このクライアントにはトレーニング記録が登録されているため削除できません。');
                            return false;
                        @else
                            return confirm('このクライアントを削除しますか？');
                        @endif
                    }
                    </script>
                @endif
            </div>
        </div>
        {{-- 下段: 属性4列 --}}
        <div class="row g-3 mt-2 pt-2 border-top">
            <div class="col-md-3">
                <div class="text-muted small">主担当</div>
                <div style="min-height: 1.5rem;">{{ $client->primaryTrainer?->name ?: '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">初回日</div>
                <div style="min-height: 1.5rem;">{{ $client->initial_consultation_date?->format('Y/m/d') ?: '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">生年月日</div>
                <div style="min-height: 1.5rem;">{{ $client->birth_date?->format('Y/m/d') ?: '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">性別</div>
                <div style="min-height: 1.5rem;">{{ $client->gender ?: '—' }}</div>
            </div>
        </div>
    </div>

    @if($activeIntakeToken)
        @php
            $intakeUrl = route('client-intake.show-by-token', $activeIntakeToken->token);
        @endphp
        {{-- 初回情報入力URL --}}
        <div class="card mb-3" style="border-left: 4px solid #0d6efd;">
            <div class="card-header d-flex justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 text-truncate" style="min-width: 0;">
                    <h6 class="mb-0 flex-shrink-0"><i class="bi bi-link-45deg"></i> 初回情報入力URL</h6>
                    <span class="text-muted text-truncate" title="{{ $intakeUrl }}">({{ $intakeUrl }})</span>
                </div>
                <div class="d-flex gap-2 align-items-center flex-shrink-0">
                    <button type="button" class="btn btn-outline-primary btn-sm"
                            onclick="copyToClipboard(this, '{{ $intakeUrl }}')">URLをコピー</button>
                    <button type="button" class="btn btn-outline-primary btn-sm"
                            onclick="showQrModal('{{ $intakeUrl }}')">QRコード</button>
                    <form method="POST"
                          action="{{ route('client-intake-tokens.destroy', [$client, $activeIntakeToken]) }}"
                          class="d-inline m-0"
                          onsubmit="return confirmDeleteIntakeToken()">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">削除</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <th class="text-muted" style="width:20%">発行日時</th>
                        <td>{{ $activeIntakeToken->created_at->format('Y/m/d H:i') }}</td>
                        <th class="text-muted" style="width:20%">有効期限</th>
                        <td>{{ $activeIntakeToken->expires_at->format('Y/m/d H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    @endif

    @push('scripts')
        @if(!$client->is_viewable && $client->email)
        <script>
        function confirmReleaseView() {
            return confirm('{{ $client->email }} に招待メールを送信し、閲覧を解放します。よろしいですか？');
        }
        </script>
        @endif
        @if($client->is_viewable)
        <script>
        function confirmRevokeView() {
            return confirm('閲覧の解放を取り消すと、このクライアントは記録を閲覧できなくなり、解放前の状態に戻ります。再び閲覧してもらうには、閲覧の解放とパスワードの再設定が必要です。よろしいですか？');
        }
        </script>
        @endif

        {{-- 初回情報入力URL 関連 --}}
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        <script>
        function copyToClipboard(button, text) {
            navigator.clipboard.writeText(text).then(function () {
                // コピー成功: tooltip で「コピーしました」を一瞬表示
                const tooltip = bootstrap.Tooltip.getOrCreateInstance(button, {
                    title: 'コピーしました',
                    trigger: 'manual',
                    placement: 'top',
                });
                tooltip.show();
                setTimeout(function () {
                    tooltip.hide();
                }, 1500);
            }, function () {
                // コピー失敗: 従来どおり alert（見逃さないため）
                alert('コピーに失敗しました');
            });
        }

        function showQrModal(url) {
            const qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = '';
            new QRCode(qrContainer, {
                text: url,
                width: 256,
                height: 256,
                correctLevel: QRCode.CorrectLevel.M,
            });
            document.getElementById('qrUrl').textContent = url;
            const modal = new bootstrap.Modal(document.getElementById('qrModal'));
            modal.show();
        }

        function confirmDeleteIntakeToken() {
            return confirm('この初回情報入力URLを削除しますか？削除後は再発行できます。');
        }
        </script>
    @endpush

    <div class="row g-3">
        {{-- 左カラム: トレーニング記録（タイムライン） --}}
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">トレーニング記録（{{ $client->trainingRecords->count() }}件）</h6>
                    <a href="{{ route('training-records.create', ['client_id' => $client->id]) }}"
                       class="btn btn-primary">新規登録</a>
                </div>
                @if($client->trainingRecords->count() > 0)
                    <div class="card-body training-records-scroll">
                        <div class="training-records-timeline">
                            @foreach($client->trainingRecords as $record)
                                @php
                                    $isFutureDate = $record->training_date > now()->startOfDay();
                                    // 担当1・担当2 を中黒で連結。両方空なら行ごと非表示
                                    $trainerNames = implode('・', array_filter([
                                        $record->trainer1?->name,
                                        $record->trainer2?->name,
                                    ]));
                                @endphp
                                <a href="{{ route('training-records.show', $record) }}" class="record-block-link">
                                    <div class="record-block">
                                        {{-- 1行目: 日付 + 時刻 + トレーニング内容バッジ --}}
                                        <div class="record-block__line1">
                                            <span @if($isFutureDate) class="text-primary" @endif>{{ $record->training_date->format('Y/m/d') }}</span>
                                            @if($record->training_time)
                                                <span>{{ substr($record->training_time, 0, 5) }}</span>
                                            @endif
                                            @if($record->trainingType)
                                                <span class="badge bg-light text-dark border">{{ $record->trainingType->name }}</span>
                                            @endif
                                        </div>
                                        {{-- 2行目: 担当 --}}
                                        @if($trainerNames !== '')
                                            <div class="record-block__line2"><span class="text-muted">担当</span> {{ $trainerNames }}</div>
                                        @endif
                                        {{-- 3行目: 記録本文 --}}
                                        @if($record->record_content)
                                            <div class="record-block__line3">{{ $record->record_content }}</div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <p class="text-muted mb-0">トレーニング記録はありません</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- 右カラム: 予備カード --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">（未定）</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">（将来の拡張用）</p>
                </div>
            </div>
        </div>
    </div>

    {{-- カテゴリー2: 連絡先 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-contact" style="cursor: pointer;">
            <h6 class="mb-0">連絡先</h6>
            @if($client->phone1 || $client->address1)
                <span class="badge bg-success">入力あり</span>
            @else
                <span class="badge bg-light text-muted">入力なし</span>
            @endif
        </div>
        <div class="collapse" id="section-contact">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">電話番号1</th><td>{{ $client->phone1 ?: '—' }}</td></tr>
                            <tr><th class="text-muted">電話番号2</th><td>{{ $client->phone2 ?: '—' }}</td></tr>
                            <tr><th class="text-muted">メールアドレス</th><td>{{ $client->email ?: '—' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">郵便番号</th><td>{{ $client->postal_code ?: '—' }}</td></tr>
                            <tr><th class="text-muted">住所</th>
                                <td>
                                    @if($client->address1 || $client->address2 || $client->address3 || $client->address4)
                                        {{ $client->address1 }}{{ $client->address2 }}{{ $client->address3 }}<br>{{ $client->address4 }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 最終更新 --}}
    <div class="text-end text-muted small mb-3">
        最終更新: {{ $client->updated_at->format('Y/m/d H:i') }} {{ $client->updatedBy?->name ?: '—' }}
    </div>

    {{-- URL発行モーダル（S-0305-M01） --}}
    <div class="modal fade" id="issueIntakeTokenModal" tabindex="-1" aria-labelledby="issueIntakeTokenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('client-intake-tokens.store', $client) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="issueIntakeTokenModalLabel">初回情報入力URLの発行</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="expires_in_days" class="form-label">有効期限 <span class="text-danger">*</span></label>
                            <select class="form-select" id="expires_in_days" name="expires_in_days" required>
                                <option value="1" {{ old('expires_in_days') == 1 ? 'selected' : '' }}>1日後</option>
                                <option value="7" {{ old('expires_in_days', 7) == 7 ? 'selected' : '' }}>7日後</option>
                                <option value="14" {{ old('expires_in_days') == 14 ? 'selected' : '' }}>14日後</option>
                                <option value="30" {{ old('expires_in_days') == 30 ? 'selected' : '' }}>30日後</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                        <button type="submit" class="btn btn-success">発行</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- QRコード表示モーダル（S-0305-M02） --}}
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalLabel">QRコード</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qrcode" class="d-inline-block"></div>
                    <p class="text-muted small text-break mt-3 mb-0" id="qrUrl"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
