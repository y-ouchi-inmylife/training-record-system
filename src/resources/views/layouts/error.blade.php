<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="/icons/favicon-trainer.ico" sizes="any">
    <link rel="icon" href="/icons/favicon-trainer.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon-trainer.png">
    <link rel="manifest" href="/manifest-trainer.json">
    <meta name="theme-color" content="#0a4fa8">
    <title>@hasSection('title')@yield('title') - @endif{{ config('app.trainer_portal_name') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    {{-- エラー画面（S-0103 / S-0104）用の中立レイアウト。
         ログイン中ユーザーに依存する表示（トレーナー用ナビの isSystemAdmin()、クライアント用ナビの
         full_name 等）を一切持たない。両 guard の認証状態に関わらず同一表示となる。
         container は content 側に任せる（403/419 のカードが自前で container を持つため二重化を避ける）。 --}}
    <main class="py-4">
        @yield('content')
    </main>
</body>
</html>
