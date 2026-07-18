@extends('layouts.app')

@section('title', 'クライアント詳細')

@push('styles')
<style>
.training-records-scroll {
    max-height: 200px;
    overflow-y: auto;
    overflow-x: auto;
}
.training-records-scroll::-webkit-scrollbar {
    width: 8px;
}
.training-records-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}
.training-records-scroll::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}
.training-records-scroll::-webkit-scrollbar-thumb:hover {
    background: #555;
}
.training-records-scroll thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background-color: #f8f9fa;
}
</style>
@endpush

@section('content')
<div class="container">
    {{-- ヘッダー --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">クライアント詳細</h2>
        <div class="d-flex gap-2 align-items-center">
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

    {{-- カテゴリー1: 基本情報（閲覧管理を統合） --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">基本情報</h6>
            <div>
                @if(!$client->is_viewable && $client->email)
                    {{-- B: 未解放・メール有 --}}
                    <form method="POST" action="{{ route('client-view-release.store', $client) }}"
                          onsubmit="return confirmReleaseView()" class="d-inline m-0">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">閲覧を解放する</button>
                    </form>
                @elseif($client->is_viewable)
                    {{-- C・D: 解放済み --}}
                    <form method="POST" action="{{ route('client-view-revoke.store', $client) }}"
                          onsubmit="return confirmRevokeView()" class="d-inline m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">閲覧の解放を取り消す</button>
                    </form>
                @endif
                {{-- A: メール未登録は何も出さない --}}
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {{-- 閲覧状態を左カラム表の4行目として組み込む。以前は行外の全幅テーブルに
                         切り出されていて、テーブル間の下マージンで隙間が空いていた。 --}}
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted" style="width:40%">内部ID</th><td>{{ $client->internal_id }}</td></tr>
                        <tr>
                            <th class="text-muted">名前</th>
                            <td>{{ $client->full_name }} <span class="text-muted">{{ $client->full_name_kana ? '（' . $client->full_name_kana . '）' : '' }}</span></td>
                        </tr>
                        <tr><th class="text-muted">メールアドレス</th><td>{{ $client->email ?: '—' }}</td></tr>
                        <tr>
                            <th class="text-muted">閲覧状態</th>
                            <td>
                                @if(!$client->is_viewable && empty($client->email))
                                    <span class="badge bg-secondary fs-6">メールアドレス未登録</span>
                                @elseif(!$client->is_viewable)
                                    <span class="badge bg-secondary fs-6">未解放</span>
                                @elseif(empty($client->password))
                                    <span class="badge bg-warning text-dark fs-6">解放中（パスワード未設定）</span>
                                @else
                                    <span class="badge bg-success fs-6">解放中</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted" style="width:40%">初回日</th><td>{{ $client->initial_consultation_date?->format('Y/m/d') ?: '—' }}</td></tr>
                        <tr><th class="text-muted">生年月日</th><td>{{ $client->birth_date?->format('Y/m/d') ?: '—' }}</td></tr>
                        <tr><th class="text-muted">性別</th><td>{{ $client->gender ?: '—' }}</td></tr>
                        <tr><th class="text-muted">主担当</th><td>{{ $client->primaryTrainer?->name ?: '—' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

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

        {{-- 事前入力URL 関連 --}}
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
            return confirm('この事前入力URLを削除しますか？削除後は再発行できます。');
        }
        </script>
    @endpush

    {{-- トレーニング記録一覧 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                トレーニング記録（{{ $client->trainingRecords->count() }}件）
            </h6>
            <a href="{{ route('training-records.create', ['client_id' => $client->id]) }}" class="btn btn-primary">新規登録</a>
        </div>
        @if($client->trainingRecords->count() > 0)
            <div class="training-records-scroll">
                <table class="table table-hover table-sm mb-0 training-records-table">
                    <thead class="table-light">
                        <tr>
                            <th>日付</th>
                            <th>担当1</th>
                            <th>担当2</th>
                            <th>トレーニング内容</th>
                            <th>フェーズ</th>
                            <th>メディア</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($client->trainingRecords as $record)
                            <tr style="cursor: pointer;" onclick="location.href='{{ route('training-records.show', $record) }}'">
                                <td>
                                    @if($record->training_date > now()->startOfDay())
                                        <span class="text-primary">{{ $record->training_date->format('Y/m/d') }}</span>
                                    @else
                                        {{ $record->training_date->format('Y/m/d') }}
                                    @endif
                                </td>
                                <td>{{ $record->trainer1->name ?? '—' }}</td>
                                <td>{{ $record->trainer2->name ?? '—' }}</td>
                                <td>{{ $record->trainingType->name ?? '—' }}</td>
                                <td>{{ $record->phase->name ?? '—' }}</td>
                                <td>{{ $record->media_records_count > 0 ? $record->media_records_count : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body">
                <p class="text-muted mb-0">トレーニング記録はありません</p>
            </div>
        @endif
    </div>

    {{-- 事前入力URL --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">事前入力URL</h6>
        </div>
        <div class="card-body">
            @if($activeIntakeToken)
                @php
                    $intakeUrl = route('client-intake.show-by-token', $activeIntakeToken->token);
                @endphp
                <table class="table table-borderless table-sm mb-3">
                    <tr>
                        <th class="text-muted" style="width:20%">発行日時</th>
                        <td>{{ $activeIntakeToken->created_at->format('Y/m/d H:i') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">発行者</th>
                        <td>{{ $activeIntakeToken->creator?->name ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">有効期限</th>
                        <td>{{ $activeIntakeToken->expires_at->format('Y/m/d H:i') }}</td>
                    </tr>
                </table>
                <div class="d-flex gap-2">
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
            @else
                <p class="text-muted mb-0">発行済みのURLはありません</p>
            @endif
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
                        <h5 class="modal-title" id="issueIntakeTokenModalLabel">事前入力URLの発行</h5>
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
                        <button type="submit" class="btn btn-primary">発行</button>
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
