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
    <main class="py-4">
        <div class="container">
            @yield('content')
        </div>
    </main>
</body>
</html>
