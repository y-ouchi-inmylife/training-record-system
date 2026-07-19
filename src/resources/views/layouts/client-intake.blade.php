<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>情報入力</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <main class="py-3">
        <div class="container">
            @yield('content')
        </div>
    </main>
</body>
</html>
