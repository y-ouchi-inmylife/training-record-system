@extends('layouts.app')

@section('title', 'トレーニング記録登録')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">トレーニング記録登録</h2>
        <div class="d-flex gap-2">
            <a href="{{ $selectedClientId ? route('clients.show', $selectedClientId) : route('training-records.index') }}" class="btn btn-secondary js-leave-link">キャンセル</a>
            <button type="submit" form="trainingRecordForm" class="btn btn-success">登録</button>
        </div>
    </div>

    @include('training-records._form', [
        'action' => route('training-records.store'),
        'method' => 'POST',
        'record' => null,
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
            formSelector: '#trainingRecordForm',
            leaveLinkSelector: '.js-leave-link'
        }).init();
    });
</script>
@endpush
