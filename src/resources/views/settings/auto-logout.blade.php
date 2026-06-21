@extends('layouts.app')

@section('title', '自動ログアウト')

@section('content')
<div class="container">
    <form method="POST" action="{{ route('settings.auto-logout.update') }}">
        @csrf
        @method('PUT')

        <div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 700px;">
            <h2 class="mb-0">自動ログアウト</h2>
            <button type="submit" class="btn btn-success">更新</button>
        </div>

        <div class="mb-2">
            <select class="form-select @error('auto_logout_minutes') is-invalid @enderror"
                    id="auto_logout_minutes" name="auto_logout_minutes"
                    style="max-width: 250px;">
                <option value="0"
                    {{ old('auto_logout_minutes', $settings['auto_logout_minutes'] ?? '0') == 0 ? 'selected' : '' }}>
                    設定しない
                </option>
                @foreach([3, 5, 10, 15, 30, 60] as $minutes)
                    <option value="{{ $minutes }}"
                        {{ old('auto_logout_minutes', $settings['auto_logout_minutes'] ?? '0') == $minutes ? 'selected' : '' }}>
                        {{ $minutes }}分
                    </option>
                @endforeach
            </select>
            @error('auto_logout_minutes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <ul class="mt-1 mb-3" style="max-width: 700px;">
            <li>マウス操作・キーボード入力・クリックなどのユーザー操作がない場合、設定した時間経過後に自動的にログアウトします。</li>
            <li>「設定しない」を選択した場合、自動ログアウトは無効になります。</li>
            <li>操作があるたびにタイマーがリセットされます。</li>
        </ul>
    </form>
</div>
@endsection
