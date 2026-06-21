@extends('layouts.app')

@section('title', 'クライアント登録（URL発行）管理')

@section('content')
<div class="container">
    <h2 class="mb-4">クライアント登録（URL発行）管理</h2>

    {{-- URL発行フォーム --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">新しいURLを発行</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('client-intake-tokens.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="expires_in_days" class="form-label">有効期限</label>
                    <select class="form-select @error('expires_in_days') is-invalid @enderror"
                            id="expires_in_days" name="expires_in_days">
                        <option value="1" {{ old('expires_in_days') == 1 ? 'selected' : '' }}>1日後</option>
                        <option value="7" {{ old('expires_in_days', 7) == 7 ? 'selected' : '' }}>7日後</option>
                        <option value="14" {{ old('expires_in_days') == 14 ? 'selected' : '' }}>14日後</option>
                        <option value="30" {{ old('expires_in_days') == 30 ? 'selected' : '' }}>30日後</option>
                    </select>
                    @error('expires_in_days')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="initial_consultation_date" class="form-label">初回日 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control datepicker @error('initial_consultation_date') is-invalid @enderror"
                           id="initial_consultation_date" name="initial_consultation_date"
                           value="{{ old('initial_consultation_date') }}"
                           placeholder="例: 2026-04-01" pattern="\d{4}-\d{2}-\d{2}" maxlength="10" required>
                    @error('initial_consultation_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">メールアドレス（任意） <small class="text-muted fw-normal">将来的にメール送信機能で使用します</small></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                           id="email" name="email" value="{{ old('email') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="memo" class="form-label">メモ（任意）</label>
                    <input type="text" class="form-control @error('memo') is-invalid @enderror"
                           id="memo" name="memo" value="{{ old('memo') }}"
                           placeholder="例: 山田太郎様 初回予定 4/10">
                    @error('memo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-success">URL発行</button>
            </form>
        </div>
    </div>

    {{-- 発行済みURL一覧 --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">発行済みURL一覧</h5>
        </div>
        <div class="card-body">
            @if ($tokens->count() > 0)
                @foreach ($tokens as $token)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <p class="mb-1">
                                        <strong>発行日時:</strong> {{ $token->created_at->format('Y/m/d H:i') }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>発行者:</strong> {{ $token->creator->name ?? '—' }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>有効期限:</strong> {{ $token->expires_at->format('Y/m/d H:i') }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>初回日:</strong> {{ $token->initial_consultation_date?->format('Y/m/d') ?: '—' }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>メールアドレス:</strong> {{ $token->email ?: '—' }}
                                    </p>
                                    <p class="mb-1">
                                        <strong>メモ:</strong> {{ $token->memo ?: '—' }}
                                    </p>
                                    <p class="mb-0">
                                        <strong>状態:</strong>
                                        <span class="badge {{ $token->status_badge_class }}">{{ $token->status }}</span>
                                        @if ($token->is_used && $token->client_id)
                                            <span class="text-muted">（内部ID: {{ $token->client->internal_id ?? $token->client_id }}）</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="button" class="btn btn-sm btn-secondary mb-2"
                                            onclick="copyToClipboard('{{ route('client-intake.show-by-token', $token->token) }}')">
                                        URLをコピー
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary mb-2"
                                            onclick="showQrModal('{{ route('client-intake.show-by-token', $token->token) }}')">
                                        QRコード
                                    </button>
                                    @if (!$token->is_used && !$token->isExpired())
                                        <form method="POST" action="{{ route('client-intake-tokens.destroy', $token->id) }}"
                                              style="display:inline;" onsubmit="return confirm('このURLを削除しますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger mb-2">削除</button>
                                        </form>
                                    @endif
                                    @if ($token->is_used && $token->client_id)
                                        <a href="{{ route('clients.show', $token->client_id) }}"
                                           class="btn btn-sm btn-outline-secondary mb-2">詳細を見る</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- ページネーション --}}
                <div class="d-flex justify-content-center">
                    {{ $tokens->links() }}
                </div>
            @else
                <p class="text-muted">発行済みのURLはありません</p>
            @endif
        </div>
    </div>
</div>

{{-- QRコード表示モーダル --}}
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

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('URLをクリップボードにコピーしました');
    }, function(err) {
        alert('コピーに失敗しました');
    });
}

// QRコードモーダルを表示し、指定URLのQRコードを生成する
function showQrModal(url) {
    const qrContainer = document.getElementById('qrcode');
    // 前回生成したQRコードをクリア
    qrContainer.innerHTML = '';
    // QRコードを生成（256x256px）
    new QRCode(qrContainer, {
        text: url,
        width: 256,
        height: 256,
        correctLevel: QRCode.CorrectLevel.M,
    });
    // URLをテキスト表示
    document.getElementById('qrUrl').textContent = url;
    // モーダル表示
    const modal = new bootstrap.Modal(document.getElementById('qrModal'));
    modal.show();
}
</script>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr('.datepicker', {
            locale: 'ja',
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
        });
    });
</script>
@endpush
