@extends('layouts.app')

@section('title', 'トレーニング記録編集')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">トレーニング記録編集</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('clients.show', $counselingRecord->client_id) }}" class="btn btn-secondary js-leave-link">キャンセル</a>
            <button type="submit" form="counselingRecordForm" class="btn btn-success">更新</button>
        </div>
    </div>

    @include('counseling-records._form', [
        'action' => route('counseling-records.update', $counselingRecord),
        'method' => 'PUT',
        'record' => $counselingRecord,
    ])
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
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

        // 未保存変更警告
        new window.UnsavedChangesGuard({
            formSelector: '#counselingRecordForm',
            leaveLinkSelector: '.js-leave-link'
        }).init();
    });
</script>
@endpush
