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
.training-records-table tbody td,
.training-records-table thead th {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
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
                    <table class="table table-borderless table-sm">
                        <tr><th class="text-muted" style="width:40%">内部ID</th><td>{{ $client->internal_id }}</td></tr>
                        <tr>
                            <th class="text-muted">名前</th>
                            <td>{{ $client->full_name }} <span class="text-muted">{{ $client->full_name_kana ? '（' . $client->full_name_kana . '）' : '' }}</span></td>
                        </tr>
                        <tr><th class="text-muted">メールアドレス</th><td>{{ $client->email ?: '—' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th class="text-muted" style="width:40%">初回日</th><td>{{ $client->initial_consultation_date?->format('Y/m/d') ?: '—' }}</td></tr>
                        <tr><th class="text-muted">生年月日</th><td>{{ $client->birth_date?->format('Y/m/d') ?: '—' }}</td></tr>
                        <tr><th class="text-muted">性別</th><td>{{ $client->gender ?: '—' }}</td></tr>
                    </table>
                </div>
            </div>
            <table class="table table-borderless table-sm mb-0">
                <tr>
                    <th class="text-muted" style="width:20%">閲覧状態</th>
                    <td>
                        @if(!$client->is_viewable && empty($client->email))
                            <span class="badge bg-secondary fs-6">メールアドレス未登録</span>
                        @elseif(!$client->is_viewable)
                            <span class="badge bg-secondary fs-6">未解放</span>
                        @elseif(empty($client->password))
                            <span class="badge bg-warning text-dark fs-6">解放（パスワード未設定）</span>
                        @else
                            <span class="badge bg-success fs-6">解放</span>
                        @endif
                    </td>
                </tr>
            </table>
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

    {{-- カテゴリー7: 支援管理 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-support" style="cursor: pointer;">
            <h6 class="mb-0">支援管理</h6>
        </div>
        <div class="collapse show" id="section-support">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">主担当</th><td>{{ $client->primaryTrainer?->name ?: '—' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th class="text-muted" style="width:40%">支援状態</th>
                                @if($client->supportStatus)
                                    <td>
                                        <span class="badge bg-secondary fs-6">{{ $client->supportStatus->name }}</span>
                                    </td>
                                @else
                                    <td><span class="text-muted small" style="opacity: 0.5;">未設定</span></td>
                                @endif
                            </tr>
                        </table>
                    </div>
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

</div>
@endsection
