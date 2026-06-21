@extends('layouts.app')

@section('title', 'マイプロフィール')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 700px;">
                <h2 class="mb-0">マイプロフィール</h2>
                <div class="d-flex gap-2">
                    <button type="submit" form="profile-edit-form" class="btn btn-success">更新</button>
                </div>
            </div>

            <form id="profile-edit-form" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="login_id" class="form-label">ログインID</label>
                    <input type="text" id="login_id" class="form-control"
                           value="{{ $counselor->login_id }}" disabled
                           style="max-width: 700px;">
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name"
                           class="form-control @error('name') is-invalid @enderror"
                           inputmode="text"
                           value="{{ old('name', $counselor->name) }}" maxlength="100" required
                           style="max-width: 700px;">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">権限</label>
                    <input type="text" id="role" class="form-control"
                           value="{{ $counselor->role_display_name }}" disabled
                           style="max-width: 700px;">
                </div>
            </form>

            <div class="mt-4">
                <a href="{{ route('profile.password.edit') }}">パスワード変更</a>
            </div>
        </div>
    </div>
</div>
@endsection
