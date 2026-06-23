@extends('layouts.app')

@section('title', '音声ファイル一覧')

@section('content')
<div class="container">
    <h2 class="mb-4">音声ファイル一覧</h2>

    {{-- 全体統計 --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">総ファイル数</h6>
                    <h3 class="card-title">{{ number_format($totalStats['total_files']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">総容量</h6>
                    <h3 class="card-title">{{ formatFileSize($totalStats['total_size']) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>日時</th>
                    <th>表示名</th>
                    <th>登録者</th>
                    <th>ファイル名</th>
                    <th>使用容量</th>
                </tr>
            </thead>
            <tbody>
                @forelse($audioRecords as $audioRecord)
                <tr>
                    <td>{{ $audioRecord->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $audioRecord->title }}</td>
                    <td>{{ $audioRecord->trainer->name ?? '—' }}</td>
                    <td>{{ $audioRecord->file_name ?? $audioRecord->title }}</td>
                    <td>{{ formatFileSize($audioRecord->file_size) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">データがありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($audioRecords->hasPages())
        <div class="mt-3">
            {{ $audioRecords->links() }}
        </div>
    @endif
</div>
@endsection
