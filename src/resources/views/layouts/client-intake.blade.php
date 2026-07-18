<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>情報入力</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <main class="py-3">
        <div class="container">
            @yield('content')
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr('.datepicker', {
                locale: 'ja',
                dateFormat: 'Y-m-d',
                allowInput: true,
                disableMobile: true,
            });
        });
    </script>
</body>
</html>
