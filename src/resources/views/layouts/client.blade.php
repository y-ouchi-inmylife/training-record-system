<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'クライアント閲覧 - トレーニング記録管理システム')</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    {{-- 段2の暫定：ログイン中のクライアントに最小のログアウトボタンを表示。段3で正式なナビに置き換える。 --}}
    @auth('client')
    <div class="container mt-3 d-flex justify-content-end">
        <form method="POST" action="{{ route('client-portal.logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">ログアウト</button>
        </form>
    </div>
    @endauth

    <main>
        @yield('content')
    </main>
</body>
</html>
