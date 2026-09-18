@extends('layouts.app')

@section('title', 'トレーニー詳細')

@section('content')
<div class="container">
    {{-- ヘッダー: 戻る + 操作ボタン --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('clients.show', $trainee->client) }}" class="btn btn-outline-secondary">&laquo; 会員詳細へ戻る</a>
        <div class="d-flex gap-2">
            <a href="{{ route('trainees.edit', $trainee) }}" class="btn btn-primary">編集</a>
            @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('trainees.destroy', $trainee) }}" class="d-inline"
                      onsubmit="return confirm('このトレーニーを削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">削除</button>
                </form>
            @endif
        </div>
    </div>

    {{-- 会員名（トレーニーは会員に紐づくため上部に対象会員を明示） --}}
    <div class="mb-3">
        <span class="text-muted small">会員</span>
        <span class="ms-2">{{ $trainee->client->full_name }}</span>
    </div>

    {{-- 氏名見出し --}}
    <h2 class="mb-3">{{ $trainee->name }}</h2>

    {{-- 基本情報カード --}}
    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0">基本情報</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <x-detail-cell label="犬種" :value="$trainee->breed ?: '—'" />
                <x-detail-cell label="性別" :value="$trainee->sex_label ?: '—'" />
                <x-detail-cell label="誕生日" :value="$trainee->birth_date ? $trainee->birth_date->format('Y/m/d') : '—'" />
                @if($trainee->age !== null)
                    <x-detail-cell label="年齢" :value="$trainee->age . '歳'" />
                @endif
            </div>
            @if($trainee->note)
                <div class="mt-3">
                    <div class="text-muted small mb-1">備考</div>
                    <div style="white-space: pre-wrap;">{{ $trainee->note }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- 計測値の一覧・新規登録は段階②で追加する（本コミットでは未実装） --}}
</div>
@endsection
