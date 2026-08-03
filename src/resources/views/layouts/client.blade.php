<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <title>@yield('title', config('app.client_portal_name', 'トレーニング記録'))</title>
    @vite(['resources/sass/client.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    @auth('client')
    {{-- クライアント側ナビ: navbar-dark / bg-client-nav の暫定構造から
         .c-nav に移行(段階4-6)。Bootstrap の .navbar 単体では色が付かない
         ため、SCSS 側 .c-nav で mat 面 + 罫線 + navbar CSS 変数(ink 基調)を
         定義する --}}
    <nav class="navbar navbar-expand-lg c-nav">
        <div class="container">
            <span class="navbar-brand">{{ config('app.client_portal_name', 'トレーニング記録') }}</span>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">{{ auth('client')->user()->full_name }} さん</span>
                <form method="POST" action="{{ route('client-portal.logout') }}" class="m-0">
                    @csrf
                    {{-- 設計書 §6: ログアウトは主要な行為ではないため
                         btn-link で文字リンク化(装飾を落とす) --}}
                    <button type="submit" class="btn btn-link btn-sm">ログアウト</button>
                </form>
            </div>
        </div>
    </nav>
    @endauth

    <main class="py-4">
        <div class="container">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert" data-auto-dismiss>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
        @yield('content')
    </main>
    <script>
        // successフラッシュメッセージを5秒後に自動消去する（data-auto-dismiss 属性を持つ要素のみ）
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-auto-dismiss]').forEach(function (el) {
                setTimeout(function () {
                    bootstrap.Alert.getOrCreateInstance(el).close();
                }, 5000);
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
