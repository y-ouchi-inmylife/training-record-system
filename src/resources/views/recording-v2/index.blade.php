@extends('layouts.app')

@section('title', '録音準備')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">録音準備</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('recording-v2.start') }}" method="POST">
                        @csrf

                        <!-- クライアント選択（Select2） -->
                        <div class="mb-3">
                            <label class="form-label">クライアント <span class="text-danger">*</span></label>
                            <select name="client_id" class="form-select select2-client @error('client_id') is-invalid @enderror" id="client-select">
                                <option value="">クライアントを検索...</option>
                            </select>
                            <div class="invalid-feedback client-id-error">クライアントを選択してください。</div>
                            @error('client_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <strong>注意:</strong> 録音実行へ進むと、録音が完了してログアウトするまで他の画面に移動できなくなります。
                        </div>

                        <!-- 録音開始ボタン -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-mic-fill"></i> 録音実行へ進む
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    var $clientSelect = $('#client-select');

    $clientSelect.select2({
        theme: 'bootstrap-5',
        placeholder: 'クライアントを検索（内部ID、名前、かな）',
        allowClear: false,
        width: '100%',
        ajax: {
            url: '/api/clients/search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data.results };
            },
            cache: true
        },
        minimumInputLength: 1,
        language: {
            inputTooShort: function () { return '1文字以上入力してください'; },
            noResults: function () { return '該当するクライアントが見つかりません'; },
            searching: function () { return '検索中...'; }
        }
    });

    // クライアント選択時にバリデーションエラー表示を解除
    $clientSelect.on('change', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback.client-id-error').hide();
        }
    });

    // フォーム submit 時のクライアント側バリデーション
    $clientSelect.closest('form').on('submit', function(e) {
        if (!$clientSelect.val()) {
            e.preventDefault();
            $clientSelect.addClass('is-invalid');
            $clientSelect.siblings('.invalid-feedback.client-id-error').show();
            return false;
        }
    });
});
</script>
@endpush
