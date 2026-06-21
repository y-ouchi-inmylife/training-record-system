@extends('layouts.app')

@section('title', 'クライアント詳細')

@push('styles')
<style>
.consultation-records-scroll {
    max-height: 200px;
    overflow-y: auto;
    overflow-x: auto;
}
.consultation-records-scroll::-webkit-scrollbar {
    width: 8px;
}
.consultation-records-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}
.consultation-records-scroll::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}
.consultation-records-scroll::-webkit-scrollbar-thumb:hover {
    background: #555;
}
.consultation-records-scroll thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background-color: #f8f9fa;
}
.consultation-records-table tbody td,
.consultation-records-table thead th {
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
                    @if($client->counselingRecords->count() > 0)
                        alert('このクライアントには相談記録が登録されているため削除できません。');
                        return false;
                    @else
                        return confirm('このクライアントを削除しますか？');
                    @endif
                }
                </script>
            @endif
        </div>
    </div>

    {{-- カテゴリー1: 基本情報 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">基本情報</h6>
        </div>
        <div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">内部ID</th><td>{{ $client->internal_id }}</td></tr>
                            <tr>
                                <th class="text-muted">名前（本人）</th>
                                <td>{{ $client->full_name }} <span class="text-muted">{{ $client->full_name_kana ? '（' . $client->full_name_kana . '）' : '' }}</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted">名前（家族など）</th>
                                <td>
                                    {{ $client->family_full_name ?: '—' }}
                                    @if($client->family_last_name_kana || $client->family_first_name_kana)
                                        <span class="text-muted">（{{ trim(($client->family_last_name_kana ?? '') . ' ' . ($client->family_first_name_kana ?? '')) }}）</span>
                                    @endif
                                </td>
                            </tr>
                            <tr><th class="text-muted">本人との関係</th><td>{{ $client->family_relationship ?: '—' }} {{ $client->family_relationship_detail ? '（' . $client->family_relationship_detail . '）' : '' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">初回相談日</th><td>{{ $client->initial_consultation_date?->format('Y/m/d') ?: '—' }}</td></tr>
                            <tr><th class="text-muted">生年月日（本人）</th><td>{{ $client->birth_date?->format('Y/m/d') ?: '—' }}</td></tr>
                            <tr><th class="text-muted">初回時年齢（本人）</th><td>{{ $client->initial_age ?: '—' }}</td></tr>
                            <tr><th class="text-muted">性別（本人）</th><td>{{ $client->gender ?: '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 相談記録一覧 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                相談記録（{{ $client->counselingRecords->count() }}件）
            </h6>
            <a href="{{ route('counseling-records.create', ['client_id' => $client->id]) }}" class="btn btn-primary">新規登録</a>
        </div>
        @if($client->counselingRecords->count() > 0)
            <div class="consultation-records-scroll">
                <table class="table table-hover table-sm mb-0 consultation-records-table">
                    <thead class="table-light">
                        <tr>
                            <th>相談日</th>
                            <th>参加者</th>
                            <th>担当1</th>
                            <th>担当2</th>
                            <th>参加状況</th>
                            <th>参加形態</th>
                            <th>初回</th>
                            <th>相談内容</th>
                            <th>フェーズ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($client->counselingRecords as $record)
                            <tr style="cursor: pointer;" onclick="location.href='{{ route('counseling-records.show', $record) }}'">
                                <td>
                                    @if($record->consultation_date > now()->startOfDay())
                                        <span class="text-primary">{{ $record->consultation_date->format('Y/m/d') }}</span>
                                    @else
                                        {{ $record->consultation_date->format('Y/m/d') }}
                                    @endif
                                </td>
                                <td>{{ $record->participants->pluck('participant_type')->filter()->implode('、') ?: '—' }}</td>
                                <td>{{ $record->counselor1->name ?? '—' }}</td>
                                <td>{{ $record->counselor2->name ?? '—' }}</td>
                                <td>{{ $record->attendance ?? '—' }}</td>
                                <td>{{ $record->consultation_format ?? '—' }}</td>
                                <td>{{ $record->is_intake ? '●' : '' }}</td>
                                <td>{{ $record->consultationType->name ?? '—' }}</td>
                                <td>{{ $record->phase->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body">
                <p class="text-muted mb-0">相談記録はありません</p>
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
                            <tr><th class="text-muted" style="width:40%">主担当</th><td>{{ $client->primaryCounselor?->name ?: '—' }}</td></tr>
                            <tr><th class="text-muted">連携機関</th><td>{!! nl2br(e($client->cooperating_agencies ?: '—')) !!}</td></tr>
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
            @if($client->phone1 || $client->email || $client->address1)
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
                            <tr><th class="text-muted">電話番号3（緊急連絡先）</th><td>{{ $client->phone3 ?: '—' }}</td></tr>
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
                            <tr><th class="text-muted">最寄り駅</th><td>{{ $client->nearest_station ?: '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- カテゴリー3: 学歴 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-education" style="cursor: pointer;">
            <h6 class="mb-0">学歴</h6>
            @if($client->education_level)
                <span class="badge bg-success">入力あり</span>
            @else
                <span class="badge bg-light text-muted">入力なし</span>
            @endif
        </div>
        <div class="collapse" id="section-education">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">学歴</th><td>{{ $client->education_level ?: '—' }}</td></tr>
                            <tr><th class="text-muted">詳細</th><td>{{ $client->education_detail ?: '—' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">状態</th><td>{{ $client->education_status ?: '—' }} {{ $client->education_dropout_expected ? '（中退見込）' : '' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- カテゴリー4: 職歴 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-employment" style="cursor: pointer;">
            <h6 class="mb-0">職歴</h6>
            @if($client->employment_type)
                <span class="badge bg-success">入力あり</span>
            @else
                <span class="badge bg-light text-muted">入力なし</span>
            @endif
        </div>
        <div class="collapse" id="section-employment">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">雇用形態</th><td>{{ $client->employment_type ?: '—' }}</td></tr>
                            <tr><th class="text-muted">雇用期間</th><td>{{ $client->employment_period ?: '—' }}</td></tr>
                            <tr><th class="text-muted">詳細</th><td>{!! nl2br(e($client->employment_detail ?: '—')) !!}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">週の労働時間</th><td>{{ $client->employment_hours ?: '—' }}</td></tr>
                            <tr><th class="text-muted">無職期間</th><td>{{ $client->unemployment_period ?: '—' }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- カテゴリー5: 障害・医療情報 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-disability" style="cursor: pointer;">
            <h6 class="mb-0">障害・医療情報</h6>
            @if($client->disability_physical || $client->disability_mental || $client->disability_intellectual)
                <span class="badge bg-success">入力あり</span>
            @else
                <span class="badge bg-light text-muted">入力なし</span>
            @endif
        </div>
        <div class="collapse" id="section-disability">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">身体障害者手帳</th><td>{{ $client->disability_physical ?: '—' }} {{ $client->disability_physical_grade ? '（' . $client->disability_physical_grade . '）' : '' }}</td></tr>
                            <tr><th class="text-muted">療育手帳</th><td>{{ $client->disability_intellectual ?: '—' }} {{ $client->disability_intellectual_grade ? '（' . $client->disability_intellectual_grade . '）' : '' }}</td></tr>
                            <tr><th class="text-muted">通院先</th><td>{!! nl2br(e($client->hospital ?: '—')) !!}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">精神障害者保健福祉手帳</th><td>{{ $client->disability_mental ?: '—' }} {{ $client->disability_mental_grade ? '（' . $client->disability_mental_grade . '）' : '' }}</td></tr>
                            <tr><th class="text-muted">詳細</th><td>{!! nl2br(e($client->disability_detail ?: '—')) !!}</td></tr>
                            <tr><th class="text-muted">服薬</th><td>{!! nl2br(e($client->medication ?: '—')) !!}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- カテゴリー6: 生活状況 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#section-living" style="cursor: pointer;">
            <h6 class="mb-0">生活状況</h6>
            @if($client->financial_status || $client->hikikomori || $client->school_refusal || $client->bullying)
                <span class="badge bg-success">入力あり</span>
            @else
                <span class="badge bg-light text-muted">入力なし</span>
            @endif
        </div>
        <div class="collapse" id="section-living">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">経済状態</th><td>{{ $client->financial_status ?: '—' }}</td></tr>
                            <tr><th class="text-muted">ひきこもり経験</th><td>{{ $client->hikikomori ?: '—' }}</td></tr>
                            <tr><th class="text-muted">いじめを受けた経験</th><td>{{ $client->bullying ?: '—' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr><th class="text-muted" style="width:40%">経済状態（詳細）</th><td>{!! nl2br(e($client->financial_detail ?: '—')) !!}</td></tr>
                            <tr><th class="text-muted">不登校経験</th><td>{{ $client->school_refusal ?: '—' }}</td></tr>
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
