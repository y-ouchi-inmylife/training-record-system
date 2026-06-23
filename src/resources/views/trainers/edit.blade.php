@extends('layouts.app')

@section('title', 'トレーナー編集')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 700px;">
                <h2 class="mb-0">トレーナー編集</h2>
                <div class="d-flex gap-2">
                    <button type="submit" form="trainer-edit-form" class="btn btn-success">更新</button>
                    <a href="{{ route('trainers.index') }}" class="btn btn-secondary">キャンセル</a>
                </div>
            </div>

            <form id="trainer-edit-form" method="POST" action="{{ route('trainers.update', $trainer) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">ログインID</label>
                    <input type="text" class="form-control" value="{{ $trainer->login_id }}" disabled
                           style="max-width: 700px;">
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name"
                           class="form-control @error('name') is-invalid @enderror"
                           inputmode="text"
                           value="{{ old('name', $trainer->name) }}" maxlength="100" required
                           style="max-width: 700px;">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="role" class="form-label">権限 <span class="text-danger">*</span></label>
                    <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required
                            style="max-width: 250px;">
                        <option value="staff" {{ old('role', $trainer->role) === 'staff' ? 'selected' : '' }}>一般</option>
                        <option value="admin" {{ old('role', $trainer->role) === 'admin' ? 'selected' : '' }}>管理者</option>
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
