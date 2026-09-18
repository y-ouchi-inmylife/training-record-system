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
    <meta name="theme-color" content="#162D66">
    <title>@hasSection('title')@yield('title') - @endif{{ config('app.client_portal_name', 'トレーニング記録') }}</title>
    @vite(['resources/sass/client.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    {{-- クライアントポータルのヘッダー帯（設計書 client-portal-design-plan.md §4-0）。
         ログイン前後で色・高さ・ブランド位置を揃えるため、共通の partial を経由する。
         post-auth は右側に「登録情報」「ログアウト」を出し、pre-auth は左のブランドのみ。 --}}
    @auth('client')
        @include('layouts.partials.client-nav', ['authed' => true])
    @else
        @include('layouts.partials.client-nav', ['authed' => false])
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
