@extends('layouts.app')

@section('title', 'トレーニー詳細')

@section('content')
<div class="container">
    {{-- ヘッダーサマリー — 会員詳細（S-0305）と同じ 3 段構成に揃える
         （1 段目: 操作ボタン列（右寄せ）／ 2 段目: 名前 + 会員リンク + 内部ID ／
          3 段目: セパレータ下に犬種・性別・誕生日を x-detail-cell で並べ、
                備考は同じ row の col-12 に置く）。詳細は設計書 S-0309 参照。 --}}
    <div class="mb-4">
        {{-- 1段目: 操作ボタン列（右寄せ）。従前の「« 会員詳細へ戻る」は廃止し、
             戻り導線は 2 段目の会員名リンクに一本化した（設計書 S-0309「設計方針」参照）。 --}}
        <div class="d-flex justify-content-end gap-2 mb-2">
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

        {{-- 2段目: トレーニー名 + 会員（S-0305 リンク）+ 内部ID
             書式は S-0305 の氏名見出し行と同じ（h2 の右横に text-muted small ラベル +
             font-monospace fs-5 値）。会員名は S-0305 へのリンクで、これが戻り導線を兼ねる。 --}}
        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
            <h2 class="mb-0">{{ $trainee->name }}</h2>
            <div class="d-flex align-items-baseline gap-2 ms-3">
                <span class="text-muted small">会員</span>
                <a href="{{ route('clients.show', $trainee->client) }}">{{ $trainee->client->full_name }}</a>
            </div>
            <div class="d-flex align-items-baseline gap-2 ms-3">
                <span class="text-muted small">内部ID</span>
                <span class="font-monospace fs-5">{{ $trainee->client->internal_id }}</span>
            </div>
        </div>

        {{-- 3段目: セパレータ下の属性。犬種・性別・誕生日・備考を md 以上で 4 列・
             モバイルで 2 列（col-6 col-md-3）に並べる。基本情報カードは介さない
             （設計書 S-0309「設計方針」参照）。誕生日は「Y/m/d（N歳）」に集約
             （年齢は Trainee モデルの age アクセサで算出。誕生日が未登録なら「—」のみ）。
             備考は改行保持（white-space: pre-wrap）が必要なため x-detail-cell を使わず
             自前で書く。x-detail-cell の cols 既定値（col-6 col-md-4）は会員詳細
             （S-0305 の連絡先セクション）向けの現状値。呼び出し側で col-6 col-md-3 を渡し、
             会員詳細への影響を出さないようにする。 --}}
        <div class="row g-3 mt-2 pt-2 border-top">
            <x-detail-cell label="犬種" :value="$trainee->breed ?: '—'" cols="col-6 col-md-3" />
            <x-detail-cell label="性別" :value="$trainee->sex_label ?: '—'" cols="col-6 col-md-3" />
            <x-detail-cell label="誕生日" cols="col-6 col-md-3">
                @if($trainee->birth_date)
                    {{ $trainee->birth_date->format('Y/m/d') }}（{{ $trainee->age }}歳）
                @else
                    —
                @endif
            </x-detail-cell>
            <div class="col-6 col-md-3">
                <div class="text-muted small mb-1">備考</div>
                <div style="min-height: 1.5rem; white-space: pre-wrap;">{{ $trainee->note ?: '—' }}</div>
            </div>
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
