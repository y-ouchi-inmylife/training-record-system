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
            <div class="d-flex align-items-baseline gap-2 ms-3">
                <span class="text-muted small">内部ID</span>
                <span class="font-monospace fs-5">{{ $client->internal_id }}</span>
            </div>
        </div>
        {{-- 3段目: 属性3列 --}}
        <div class="row g-3 mt-2 pt-2 border-top">
            <div class="col-md-3">
                <div class="text-muted small">初回日</div>
                <div style="min-height: 1.5rem;">{{ $client->initial_consultation_date?->format('Y/m/d') }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">主担当</div>
                <div style="min-height: 1.5rem;">{{ $client->primaryTrainer?->name }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">閲覧状態</div>
                <div style="min-height: 1.5rem;">
                    @if(!$client->is_viewable && empty($client->email))
                        <span class="badge bg-secondary fs-6">メールアドレス未登録</span>
                    @elseif(!$client->is_viewable)
                        <span class="badge bg-secondary fs-6">未解放</span>
                    @elseif(empty($client->password))
                        <span class="badge bg-warning text-dark fs-6">解放中（パスワード未設定）</span>
                    @else
                        <span class="badge bg-success fs-6">解放中</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

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

</div>
@endsection
