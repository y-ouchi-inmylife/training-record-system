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
            {{-- メールアドレス登録用 URL を発行（段階 4-1）— モーダルで発行・再発行を扱う --}}
            <button type="button" class="btn btn-primary"
                    data-bs-toggle="modal" data-bs-target="#emailRegistrationTokenModal">
                メールアドレス登録用 URL を発行
            </button>
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
        {{-- 2段目: 氏名行 + 状態バッジ --}}
        @php
            $badge = $client->statusBadge();
        @endphp
        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
            <h2 class="mb-0">
                {{ $client->full_name }}@if($client->full_name_kana)<span class="text-muted fs-6">（{{ $client->full_name_kana }}）</span>@endif
            </h2>
            <span class="badge fs-6 {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            <div class="d-flex align-items-baseline gap-2 ms-3">
                <span class="text-muted small">内部ID</span>
                <span class="font-monospace fs-5">{{ $client->internal_id }}</span>
            </div>
        </div>
        {{-- 3段目: 属性2列。状態バッジは段階 4-2 で追加予定 --}}
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
                <x-detail-cell label="電話番号" :value="$client->phone1" />
                <x-detail-cell label="予備の電話番号" :value="$client->phone2" />
                <x-detail-cell label="メールアドレス" :value="$client->email" />
            </div>
        </div>
    </div>

    {{-- 最終更新 --}}
    <div class="text-end text-muted small mb-3">
        最終更新: {{ $client->updated_at->format('Y/m/d H:i') }} {{ $client->updatedBy?->name ?: '—' }}
    </div>

    {{-- メールアドレス登録用 URL 発行モーダル --}}
    <div class="modal fade" id="emailRegistrationTokenModal" tabindex="-1"
         aria-labelledby="emailRegistrationTokenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailRegistrationTokenModalLabel">
                        メールアドレス登録用 URL の発行
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($emailRegistrationUrl)
                        {{-- 発行済み：URL・コピー・QR コード・印刷用ページへのリンク・有効期限・発行し直し --}}
                        <p class="mb-2">発行済みのメールアドレス登録用 URL があります。お客様にお渡しください。</p>
                        <div class="mb-3">
                            <label class="form-label small text-muted mb-1">URL</label>
                            <div class="input-group">
                                <input type="text" id="emailRegistrationUrlInput"
                                       class="form-control font-monospace" readonly
                                       value="{{ $emailRegistrationUrl }}">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="copyEmailRegistrationUrl()">コピー</button>
                            </div>
                            <div id="emailRegistrationUrlCopyStatus" class="form-text text-success" style="display: none;">
                                コピーしました
                            </div>
                        </div>
                        <div class="mb-3 text-center">
                            <label class="form-label small text-muted mb-1 d-block">QR コード</label>
                            <canvas data-qr-url="{{ $emailRegistrationUrl }}" data-qr-size="200"
                                    aria-label="メールアドレス登録用 URL の QR コード"></canvas>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">有効期限</div>
                            <div>{{ $activeEmailRegToken->expires_at->format('Y/m/d H:i') }} まで</div>
                        </div>
                        <div class="mb-3">
                            <a href="{{ route('client-email-registration-tokens.print', $client) }}"
                               class="btn btn-outline-secondary" target="_blank" rel="noopener">
                                印刷用ページを開く
                            </a>
                        </div>
                        <p class="text-muted small mb-0">
                            発行し直すと、上記の URL と、送信済みのログイン用リンクは無効になります。
                        </p>
                    @else
                        {{-- 未発行：発行の案内 --}}
                        <p class="mb-0">
                            このクライアントにメールアドレス登録用 URL を発行します。<br>
                            発行された URL をお客様にお渡しし、メールアドレスを登録してもらいます。
                        </p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">閉じる</button>
                    <form method="POST" action="{{ route('client-email-registration-tokens.store', $client) }}"
                          class="d-inline m-0">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            {{ $emailRegistrationUrl ? '発行し直す' : '発行する' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@if($emailRegistrationUrl)
    @vite(['resources/js/qr-code.js'])
@endif
@push('scripts')
<script>
// メールアドレス登録用 URL をクリップボードにコピー
function copyEmailRegistrationUrl() {
    const input = document.getElementById('emailRegistrationUrlInput');
    if (!input) return;
    // execCommand フォールバックを含む二段構え
    const doneMsg = document.getElementById('emailRegistrationUrlCopyStatus');
    const showDone = () => {
        if (!doneMsg) return;
        doneMsg.style.display = '';
        setTimeout(() => { doneMsg.style.display = 'none'; }, 2000);
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(showDone).catch(() => {
            input.select();
            document.execCommand('copy');
            showDone();
        });
    } else {
        input.select();
        document.execCommand('copy');
        showDone();
    }
}
</script>
@endpush
@endsection
