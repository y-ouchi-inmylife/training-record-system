<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="/icons/favicon-client.ico" sizes="any">
    <link rel="icon" href="/icons/favicon-client.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon-client.png">
    <link rel="manifest" href="/manifest-client.json">
    <meta name="theme-color" content="#D85A30">
    <title>@hasSection('title')@yield('title') - @endif{{ config('app.client_portal_name', 'トレーニング記録') }}</title>
    @vite(['resources/sass/client.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    @auth('client')
    {{-- クライアント側ナビ: navbar-dark / bg-client-nav の暫定構造から
         .c-nav に移行(段階4-6)。Bootstrap の .navbar 単体では色が付かない
         ため、SCSS 側 .c-nav で mat 面 + 罫線 + navbar CSS 変数(ink 基調)を
         定義する。
         ワードマークはダッシュボードへのリンク(設計書 §4-0)。
         .c-nav の --bs-navbar-brand-hover-color は <a> でのみ機能する。
         右端はログアウトのみ(設計書 §4-0)。ユーザー名は H1 の挨拶と
         重複するため表示しない。 --}}
    <nav class="navbar navbar-expand-lg c-nav">
        <div class="container">
            <a class="navbar-brand" href="{{ route('client-portal.dashboard') }}">{{ config('app.client_portal_name', 'トレーニング記録') }}</a>
            <div class="d-flex align-items-center">
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

    @auth('client')
    {{-- フッター（設計書 §4-0「フッター（認証後のみ）」）。
         layouts.client は login.blade.php からも extends されるため
         @auth('client') で囲む。ガードなしではログイン画面に到達不能な
         HOME リンクが出力され、同画面の .c-login-footer と
         コピーライトが二重になる。 --}}
    <footer class="c-footer">
        <div class="container">
            <a href="{{ route('client-portal.dashboard') }}" class="c-footer-home">HOME</a>
            @if(config('app.client_portal_company'))
                <p class="c-footer-copyright">&copy; {{ date('Y') }} {{ config('app.client_portal_company') }}</p>
            @endif
        </div>
    </footer>
    @endauth

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
