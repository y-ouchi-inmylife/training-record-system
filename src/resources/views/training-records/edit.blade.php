@extends('layouts.app')

@section('title', 'トレーニング記録編集')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">トレーニング記録編集</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('training-records.show', $trainingRecord) }}" class="btn btn-secondary js-leave-link">キャンセル</a>
            <button type="submit" form="trainingRecordForm" class="btn btn-success">更新</button>
        </div>
    </div>

    @include('training-records._form', [
        'action' => route('training-records.update', $trainingRecord),
        'method' => 'PUT',
        'record' => $trainingRecord,
    ])
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 未保存変更警告
        window.unsavedChangesGuard = new window.UnsavedChangesGuard({
            formSelector: '#trainingRecordForm',
            leaveLinkSelector: '.js-leave-link'
        });
        window.unsavedChangesGuard.init();
    });
</script>
@endpush
