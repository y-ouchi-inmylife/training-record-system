@extends('layouts.client')

@section('title', 'パスワードの変更')

@section('content')
<div class="container">
    <div class="c-settings">
        {{-- 戻る導線：「← 戻る」の形で置く（3 変更画面 S-1409/S-1410/S-1411 で共通）。
             画面名を書かないのは、将来この画面への入口が増えても文言を直さずに済むため。
             矢印は装飾なので aria-hidden にし、「戻る」の文字を読み上げソフトに読ませる。
             設計書：`client-portal-design-plan.md` §4-10、`screen-design.md` §6。 --}}
        <a href="{{ route('client-portal.profile.show') }}" class="c-detail-back">
            <span aria-hidden="true">←</span> 戻る
        </a>

        <h1 class="mb-4">パスワードの変更</h1>

        <div class="card mb-4">
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('client-portal.settings.password.update') }}">
                    @csrf
                    @method('PUT')

                    {{-- パスワードマネージャー向け username（保存パスワードの紐付け先）--}}
                    <input type="email" name="username" value="{{ $client->email }}"
                           autocomplete="username" readonly tabindex="-1" aria-hidden="true"
                           class="visually-hidden">

                    <div class="mb-3">
                        <label for="pw_current" class="form-label">現在のパスワード <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                               id="pw_current" name="current_password" required
                               autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label for="pw_new" class="form-label">新しいパスワード <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('new_password') is-invalid @enderror"
                               id="pw_new" name="new_password" required
                               autocomplete="new-password" aria-describedby="pw_new_help">
                        <div id="pw_new_help" class="form-text">
                            8 文字以上で、大文字・小文字・数字・記号をそれぞれ 1 つ以上入れてください。
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="pw_new_confirm" class="form-label">新しいパスワード（確認） <span class="text-danger">*</span></label>
                        <input type="password" class="form-control"
                               id="pw_new_confirm" name="new_password_confirmation" required
                               autocomplete="new-password">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">パスワードを変更</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
