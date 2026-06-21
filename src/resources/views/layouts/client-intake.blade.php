<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>クライアント登録 - トレーニング記録管理システム</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            font-size: 1.1rem;
            background-color: #ffffff;
        }
        .form-label {
            font-size: 1.15rem;
            font-weight: 600;
        }
        .form-control, .form-select {
            font-size: 1.1rem;
            padding: 10px 12px;
        }
        .btn-lg {
            min-width: 140px;
            font-size: 1.2rem;
            padding: 12px 40px;
        }
        /* ステップインジケーター */
        .step-badges .badge {
            font-size: 1rem;
            padding: 8px 12px;
        }
        .progress {
            height: 8px;
        }
    </style>
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
