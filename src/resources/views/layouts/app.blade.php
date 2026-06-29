<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'トレーニング記録管理システム')</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
    <style>
        /* ネストドロップダウンのスタイル */
        .dropend .dropdown-toggle::after {
            border-left: 0.3em solid transparent;
            border-right: 0;
            border-top: 0.3em solid;
            border-bottom: 0.3em solid transparent;
            margin-left: 0.5em;
            vertical-align: middle;
        }

        .dropend .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: 0;
        }

        /* 全入力フィールドのプレースホルダーを薄く表示 */
        input::placeholder,
        textarea::placeholder {
            color: #ccc !important;
            opacity: 1;
        }
    </style>
</head>
<body>
    @auth
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ url('/dashboard') }}">トレーニング記録管理システム</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    @if(!Auth::user()->isSystemAdmin())
                        {{-- 通常のトレーナー（system_admin以外） --}}
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">ダッシュボード</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('clients*') || request()->is('client-intake-tokens*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                                クライアント
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('clients.index') }}">クライアント一覧</a></li>
                                <li><a class="dropdown-item" href="{{ route('clients.create') }}">クライアント登録</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('client-intake-tokens.index') }}">クライアント登録（URL発行）管理</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('training-records*') ? 'active' : '' }}" href="{{ route('training-records.index') }}">
                                トレーニング記録
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('media-records*') ? 'active' : '' }}" href="{{ route('media-records.index') }}">
                                メディア
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('audio-records*') || request()->is('recording*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                                音声記録
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('audio-records.index') }}">音声記録一覧</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">音声記録登録</h6></li>
                                <li><a class="dropdown-item" href="{{ route('recording-v2.index') }}">録音</a></li>
                                <li><a class="dropdown-item" href="{{ route('audio-records.upload.create') }}">音声ファイル</a></li>
                                <li><a class="dropdown-item" href="{{ route('audio-records.text-paste.create') }}">文字起こしテキスト</a></li>
                            </ul>
                        </li>
                    @endif
                    @if(Auth::user()->isPractitioner())
                        {{-- レポート（admin + staff のみ表示） --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('statistics*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                                レポート
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('statistics.clients') }}">トレーニング記録数推移</a></li>
                            </ul>
                        </li>
                    @endif
                    @if(Auth::user()->isAdminOnly())
                        {{-- 業務管理者用メニュー（adminのみ、system_adminには非表示） --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('trainers*') || request()->is('access-logs*') || request()->is('settings/summary-prompts*') || request()->is('master*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                                【管理者】
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('trainers.index') }}">トレーナー管理</a></li>
                                <li><a class="dropdown-item" href="{{ route('access-logs.index') }}">トレーナー操作履歴</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('settings.summary-prompts.edit') }}">要約プロンプト</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">マスタ管理</h6></li>
                                <li><a class="dropdown-item" href="{{ route('master.support-statuses.index') }}">支援状態</a></li>
                                <li><a class="dropdown-item" href="{{ route('master.training-types.index') }}">トレーニング内容</a></li>
                                <li><a class="dropdown-item" href="{{ route('master.phases.index') }}">フェーズ</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('settings.auto-logout.edit') }}">自動ログアウト</a></li>
                            </ul>
                        </li>
                    @endif
                    @if(Auth::user()->isSystemAdmin())
                        {{-- システム管理者用メニュー（system_adminのみ） --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('usage-stats*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                                【システム管理者】
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('usage-stats.index') }}">音声ファイル一覧</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('settings.ip-restriction.edit') }}">IPアドレス制限</a></li>
                            </ul>
                        </li>
                    @endif
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            {{ Auth::user()->name }}
                            <span class="badge bg-light text-primary ms-1">
                                {{ Auth::user()->role_display_name }}
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">マイプロフィール</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.password.edit') }}">パスワード変更</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ url('/logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">ログアウト</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    @endauth

    <main class="py-4">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
        @yield('content')
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ネストドロップダウンのホバー動作
            const dropdownSubmenus = document.querySelectorAll('.dropend .dropdown-toggle');

            dropdownSubmenus.forEach(function(element) {
                element.addEventListener('mouseenter', function() {
                    const submenu = this.nextElementSibling;
                    if (submenu && submenu.classList.contains('dropdown-menu')) {
                        submenu.classList.add('show');
                    }
                });

                element.parentElement.addEventListener('mouseleave', function() {
                    const submenu = this.querySelector('.dropdown-menu');
                    if (submenu) {
                        submenu.classList.remove('show');
                    }
                });
            });
        });
    </script>
    @stack('scripts')

    {{-- 自動ログアウトタイマー --}}
    @auth
    @php
        $autoLogoutMinutes = (int) \App\Models\SystemSetting::where('key', 'auto_logout_minutes')->value('value');
    @endphp

    @if($autoLogoutMinutes > 0)
    <script>
        (function() {
            let autoLogoutTimer;
            const autoLogoutMinutes = {{ $autoLogoutMinutes }};
            const autoLogoutMs = autoLogoutMinutes * 60 * 1000;

            function startAutoLogoutTimer() {
                clearTimeout(autoLogoutTimer);
                autoLogoutTimer = setTimeout(function() {
                    // fetchでログアウトを試み、失敗時（419等）はログイン画面にリダイレクト
                    fetch('{{ route("logout") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    }).then(function() {
                        window.location.href = '/login';
                    }).catch(function() {
                        window.location.href = '/login';
                    });
                }, autoLogoutMs);
            }

            // ページ読み込み時にタイマーを開始
            startAutoLogoutTimer();

            // ユーザー操作でタイマーをリセット
            ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function(event) {
                document.addEventListener(event, function() {
                    startAutoLogoutTimer();
                }, { passive: true });
            });
        })();
    </script>
    @endif
    @endauth
</body>
</html>
