@extends('layouts.client')

@section('title', 'ダッシュボード - トレーニング記録閲覧')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-4">{{ auth('client')->user()->full_name }} さん、ようこそ</h1>

    {{-- トレーニング記録一覧（S-1402 記録表） --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">トレーニング記録（{{ $trainingRecords->count() }}件）</h6>
        </div>
        @if($trainingRecords->count() > 0)
            <div class="training-records-scroll">
                <table class="table table-hover table-sm mb-0 training-records-table">
                    <thead class="table-light">
                        <tr>
                            <th>日付</th>
                            <th>担当1</th>
                            <th>担当2</th>
                            <th>トレーニング内容</th>
                            <th>メディア</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trainingRecords as $record)
                            <tr style="cursor: pointer;"
                                onclick="location.href='{{ route('client-portal.training-records.show', $record) }}'">
                                <td>{{ $record->training_date->format('Y/m/d') }}</td>
                                <td>{{ $record->trainer1->name ?? '—' }}</td>
                                <td>{{ $record->trainer2->name ?? '—' }}</td>
                                <td>{{ $record->trainingType->name ?? '—' }}</td>
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
</div>

{{-- 記録表のスクロール＋ヘッダー固定スタイル（layouts.client に @stack が無いためインラインで定義） --}}
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
@endsection
