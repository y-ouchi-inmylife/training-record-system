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
                {{-- 削除確認の文言はコントローラ側で組み立てて渡す（$deleteConfirmMessage）。
                     Blade 内で @json() を使って onsubmit 属性に埋め込む形にすると、
                     @json() が出力する "..." が onsubmit="..." のダブルクォート境界と
                     競合して confirm() が発火せず、確認なしで削除される不具合が
                     発生した（2026-09 修正）。他の onsubmit="return confirm('...')"
                     箇所と揃えて、シングルクォート内で {{ }} で出力する素直な形に
                     している。文言にシングルクォート・改行を含めないこと。 --}}
                <form method="POST" action="{{ route('trainees.destroy', $trainee) }}" class="d-inline"
                      onsubmit="return confirm('{{ $deleteConfirmMessage }}')">
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

    {{-- 完了メッセージ（登録・更新・削除後） --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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

    {{-- 計測値カード（S-0309 セクション「計測値の登録・編集・削除」）
         毎日入力する運用で 1 年で数百件になり得るため、既存の
         「トレーニング記録」カード（S-0305 セクション2）と同じく
         スクロール領域を持たせる（max-height: 60vh）。 --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">計測値（{{ $measurementCount }}件）</h6>
            <button type="button" class="btn btn-primary"
                    onclick="window.measurementModal.openForCreate()">新規登録</button>
        </div>
        @if($measurementCount > 0)
            <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>計測日</th>
                            <th>計測時刻</th>
                            <th class="text-end">体重（kg）</th>
                            <th>備考</th>
                            <th class="text-end" style="min-width: 120px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trainee->measurements as $m)
                            <tr>
                                <td>{{ $m->measured_date->format('Y/m/d') }}</td>
                                <td>{{ substr($m->measured_time, 0, 5) }}</td>
                                <td class="text-end">{{ number_format((float) $m->weight_kg, 2) }}</td>
                                <td>{{ $m->note }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-id="{{ $m->id }}"
                                            data-measured-date="{{ $m->measured_date->format('Y-m-d') }}"
                                            data-measured-time="{{ substr($m->measured_time, 0, 5) }}"
                                            data-weight-kg="{{ $m->weight_kg }}"
                                            data-note="{{ $m->note }}"
                                            onclick="window.measurementModal.openForEdit(this.dataset)">編集</button>
                                    <form method="POST" action="{{ route('trainee-measurements.destroy', $m) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('この計測値を削除しますか？')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">削除</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body">
                <p class="text-muted mb-0">計測値は登録されていません</p>
            </div>
        @endif
    </div>

    {{-- 計測値の登録・編集モーダル（登録・編集で共用） --}}
    @include('trainees._measurement-modal')
</div>
@endsection
