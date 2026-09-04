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
        {{-- 1段目: 操作ボタン群（右寄せ） --}}
        <div class="d-flex justify-content-end gap-2 mb-2">
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">&laquo; クライアント一覧に戻る</a>
            {{-- 閲覧解放 / 解放取り消しボタン（未解放なら「解放する」、解放済みなら「解放を取り消す」を排他表示） --}}
            @if(!$client->is_viewable)
                <form method="POST" action="{{ route('client-view-release.store', $client) }}"
                      onsubmit="return confirmReleaseView()" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-primary">閲覧を解放する</button>
                </form>
            @else
                <form method="POST" action="{{ route('client-view-revoke.store', $client) }}"
                      onsubmit="return confirmRevokeView()" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">閲覧の解放を取り消す</button>
                </form>
            @endif
            @if($activeIntakeToken)
                <button type="button" class="btn btn-primary"
                        data-bs-toggle="modal" data-bs-target="#intakeUrlModal">URL発行済み・残り{{ $activeIntakeToken->remaining_days }}日</button>
            @else
                <button type="button" class="btn btn-outline-primary"
                        data-bs-toggle="modal" data-bs-target="#intakeUrlModal">URL発行</button>
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
        {{-- 2段目: 氏名行 --}}
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
            <div class="d-flex align-items-baseline gap-2 ms-3">
                <span class="text-muted small">内部ID</span>
                <span class="font-monospace fs-5">{{ $client->internal_id }}</span>
            </div>
        </div>
        {{-- 3段目: 属性2列 --}}
        <div class="row g-3 mt-2 pt-2 border-top">
            <div class="col-md-3">
                <div class="text-muted small">初回日</div>
                <div style="min-height: 1.5rem;">{{ $client->initial_consultation_date?->format('Y/m/d') }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">主担当</div>
                <div style="min-height: 1.5rem;">{{ $client->primaryTrainer?->name }}</div>
            </div>
        </div>
    </div>

    @php
        $intakeUrl = $activeIntakeToken ? route('client-intake.show-by-token', $activeIntakeToken->token) : null;
    @endphp

    @push('scripts')
        @if(!$client->is_viewable)
        <script>
        function confirmReleaseView() {
            @if(empty($client->email))
                alert('メールアドレスが未登録のため、閲覧を解放できません。編集画面でメールアドレスを登録してください。');
                return false;
            @else
                return confirm('{{ $client->email }} に招待メールを送信し、閲覧を解放します。よろしいですか？');
            @endif
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
        @if($activeIntakeToken)
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        @endif
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
        </script>

        {{-- 初回情報入力URL モーダル制御 --}}
        <script>
        (function () {
            const modalEl = document.getElementById('intakeUrlModal');
            if (!modalEl) return;

            @if($activeIntakeToken)
            // 発行済み状態: QRコード生成 / 削除確認の切り替え
            const bodyDefault = document.getElementById('intake-modal-body-default');
            const bodyConfirm = document.getElementById('intake-modal-body-confirm');
            const footerDefault = document.getElementById('intake-modal-footer-default');
            const footerConfirm = document.getElementById('intake-modal-footer-confirm');
            const qrContainer = document.getElementById('intake-qrcode');
            const btnShowConfirm = document.getElementById('btn-show-delete-confirm');
            const btnCancelConfirm = document.getElementById('btn-cancel-delete-confirm');

            function showDefault() {
                bodyDefault.style.display = '';
                footerDefault.style.display = '';
                bodyConfirm.style.display = 'none';
                footerConfirm.style.display = 'none';
            }
            function showConfirm() {
                bodyDefault.style.display = 'none';
                footerDefault.style.display = 'none';
                bodyConfirm.style.display = '';
                footerConfirm.style.display = '';
            }

            btnShowConfirm.addEventListener('click', showConfirm);
            btnCancelConfirm.addEventListener('click', showDefault);

            modalEl.addEventListener('show.bs.modal', function () {
                // 未生成のときのみ QRコードを1回だけ生成
                if (qrContainer && qrContainer.childElementCount === 0) {
                    new QRCode(qrContainer, {
                        text: @json($intakeUrl),
                        width: 200,
                        height: 200,
                        correctLevel: QRCode.CorrectLevel.M,
                    });
                }
            });
            modalEl.addEventListener('hidden.bs.modal', showDefault);
            @else
            // 未発行状態: 有効期限プレビュー
            const preview = document.getElementById('expires-at-preview');
            const radios = modalEl.querySelectorAll('input[name="expires_in_days"]');
            function updatePreview() {
                const checked = modalEl.querySelector('input[name="expires_in_days"]:checked');
                if (!checked || !preview) return;
                const days = parseInt(checked.value, 10);
                const d = new Date();
                d.setDate(d.getDate() + days);
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                preview.textContent = y + '/' + m + '/' + day + ' 23:59 まで有効';
            }
            radios.forEach(function (r) { r.addEventListener('change', updatePreview); });
            modalEl.addEventListener('show.bs.modal', updatePreview);
            document.addEventListener('DOMContentLoaded', updatePreview);

            @if($errors->has('expires_in_days'))
            // バリデーションエラー時は自動でモーダルを開く
            document.addEventListener('DOMContentLoaded', function () {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });
            @endif
            @endif
        })();
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

    {{-- 基本情報 --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">基本情報</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <x-detail-cell label="生年月日" :value="$client->birth_date?->format('Y/m/d')" />
                <x-detail-cell label="性別" :value="$client->gender" />
            </div>
        </div>
    </div>

    {{-- 連絡先 --}}
    @php
        // 都道府県・市区町村・町名番地は区切りなしで連結し、
        // 建物名・部屋番号との間だけ半角スペースを入れる。
        // 空の項目は array_filter で除外するため余分な区切りは残らない。
        $addressMain = implode('', array_filter([
            $client->address1,
            $client->address2,
            $client->address3,
        ]));
        $fullAddress = implode(' ', array_filter([$addressMain, $client->address4]));
    @endphp
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">連絡先</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <x-detail-cell label="郵便番号" :value="$client->postal_code" />
                <x-detail-cell label="住所" :value="$fullAddress" />
                <div class="col-6 col-md-4"></div>
                <x-detail-cell label="電話番号1" :value="$client->phone1" />
                <x-detail-cell label="電話番号2" :value="$client->phone2" />
                <x-detail-cell label="メールアドレス" :value="$client->email" />
            </div>
        </div>
    </div>

    {{-- 最終更新 --}}
    <div class="text-end text-muted small mb-3">
        最終更新: {{ $client->updated_at->format('Y/m/d H:i') }} {{ $client->updatedBy?->name ?: '—' }}
    </div>

    {{-- 初回情報入力URL モーダル（S-0305-M01。未発行/発行済みを1つで扱う） --}}
    <div class="modal fade" id="intakeUrlModal" tabindex="-1" aria-labelledby="intakeUrlModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @if($activeIntakeToken)
                    {{-- 発行済み状態 --}}
                    <div class="modal-header">
                        <h5 class="modal-title" id="intakeUrlModalLabel">初回情報入力URL</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                    </div>
                    <div class="modal-body" id="intake-modal-body-default">
                        <div class="mb-3">
                            <span class="badge {{ $activeIntakeToken->status_badge_class }} fs-6">{{ $activeIntakeToken->status }}</span>
                            <span class="ms-2">残り {{ $activeIntakeToken->remaining_days }}日</span>
                        </div>
                        <div class="mb-3 text-center">
                            <div id="intake-qrcode" class="d-inline-block"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small mb-1">URL</label>
                            <div class="d-flex align-items-start gap-2">
                                <code class="font-monospace flex-grow-1 user-select-all p-2 bg-light rounded" style="word-break: break-all;">{{ $intakeUrl }}</code>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                        onclick="copyToClipboard(this, '{{ $intakeUrl }}')">コピー</button>
                            </div>
                        </div>
                        <table class="table table-borderless table-sm mb-3">
                            <tr>
                                <th class="text-muted" style="width:30%">発行日時</th>
                                <td>{{ $activeIntakeToken->created_at->format('Y/m/d H:i') }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">有効期限</th>
                                <td>{{ $activeIntakeToken->expires_at->format('Y/m/d H:i') }}</td>
                            </tr>
                        </table>
                        <p class="text-muted small mb-0">新しいURLを発行するには、現在のURLを削除してください。</p>
                    </div>
                    {{-- 削除確認（初期非表示） --}}
                    <div class="modal-body" id="intake-modal-body-confirm" style="display: none;">
                        <p class="mb-2">この初回情報入力URLを削除しますか？</p>
                        <p class="text-muted small mb-0">削除後は再発行できます。</p>
                    </div>
                    <div class="modal-footer" id="intake-modal-footer-default">
                        <button type="button" class="btn btn-outline-danger me-auto" id="btn-show-delete-confirm">削除</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                    </div>
                    <div class="modal-footer" id="intake-modal-footer-confirm" style="display: none;">
                        <button type="button" class="btn btn-secondary" id="btn-cancel-delete-confirm">キャンセル</button>
                        <form method="POST"
                              action="{{ route('client-intake-tokens.destroy', [$client, $activeIntakeToken]) }}"
                              class="d-inline m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">削除する</button>
                        </form>
                    </div>
                @else
                    {{-- 未発行状態 --}}
                    <form method="POST" action="{{ route('client-intake-tokens.store', $client) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="intakeUrlModalLabel">URL発行</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="閉じる"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">
                                <strong>{{ trim($client->full_name) }}</strong> さんに初回情報を入力してもらうための URL を発行します。
                                発行後、URL または QR コードをクライアントに渡してください。
                            </p>
                            @if($latestIntakeToken && ! $latestIntakeToken->isValid())
                                <div class="alert alert-secondary py-2 mb-3">
                                    以前発行したURLは{{ $latestIntakeToken->status }}です。
                                </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">有効期限 <span class="text-danger">*</span></label>
                                <div class="btn-group d-flex" role="group" aria-label="有効期限の選択">
                                    @foreach([1, 7, 14, 30] as $days)
                                        <input type="radio" class="btn-check" name="expires_in_days"
                                               id="expires_in_days_{{ $days }}" value="{{ $days }}"
                                               {{ (int) old('expires_in_days', 7) === $days ? 'checked' : '' }} required>
                                        <label class="btn btn-outline-primary" for="expires_in_days_{{ $days }}">{{ $days }}日後</label>
                                    @endforeach
                                </div>
                                @error('expires_in_days')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="text-muted small mt-2" id="expires-at-preview" aria-live="polite"></div>
                            </div>
                            <p class="text-muted small mb-0">
                                このURLは1回のみ使用可能です。クライアントが入力を完了するとリンクは無効になります。
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                            <button type="submit" class="btn btn-primary">発行する</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
