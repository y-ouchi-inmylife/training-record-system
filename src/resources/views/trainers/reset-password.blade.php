@extends('layouts.app')

@section('title', 'パスワードリセット')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 700px;">
                <h2 class="mb-0">パスワードリセット</h2>
                <div class="d-flex gap-2">
                    <button type="submit" form="counselor-reset-password-form" class="btn btn-success"
                            onclick="return confirm('{{ $counselor->name }} のパスワードをリセットしますか？')">更新</button>
                    <a href="{{ route('trainers.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </div>

            <form id="counselor-reset-password-form" method="POST" action="{{ route('trainers.reset-password.update', $counselor) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="login_id" class="form-label">ログインID</label>
                    <input type="text" id="login_id" class="form-control"
                           value="{{ $counselor->login_id }}" disabled
                           style="max-width: 700px;">
                </div>

                <div class="mb-3">
                    <label for="counselor_name" class="form-label">名前</label>
                    <input type="text" id="counselor_name" class="form-control"
                           value="{{ $counselor->name }}" disabled
                           style="max-width: 700px;">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">新しいパスワード <span class="text-danger">*</span></label>
                    <input type="password" name="password" id="password"
                           class="form-control @error('password') is-invalid @enderror"
                           minlength="8" required
                           style="max-width: 700px;">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">新しいパスワード（確認） <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="form-control" minlength="8" required
                           style="max-width: 700px;">
                </div>

                <div class="mb-4">
                    <div class="form-text">
                        パスワード要件：
                        <ul class="mb-0">
                            <li>8文字以上</li>
                            <li>大文字、小文字、数字、記号をそれぞれ1文字以上含む</li>
                            <li>よく使われるパスワードは使用できません</li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
