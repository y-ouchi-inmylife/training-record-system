<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 未認証時のリダイレクト先。/client-portal/* 配下はクライアント用ログインへ振り分ける。
        // ※ redirectUsersTo のクライアント分岐と CheckIpRestriction の /client-portal/* 除外は塊E骨で対応。
        $middleware->redirectGuestsTo(function ($request) {
            return $request->is('client-portal/*') ? '/client-portal/login' : '/login';
        });
        // 認証済みユーザーがguestルートにアクセスした場合のリダイレクト先。
        // URL 判定を先頭に置くことで、$request->user()（デフォルトweb guard=Trainer 前提）を
        // クライアント認証済みリクエストで呼ばずに済ませる（?-> で null 経由で誤った先へ飛ぶ回避）。
        // トレーナー側: システム管理者は音声ファイル一覧（S-0701）、それ以外はダッシュボード（S-0101）へ。
        $middleware->redirectUsersTo(function ($request) {
            if ($request->is('client-portal/*')) {
                return '/client-portal/dashboard';
            }
            return $request->user()?->isSystemAdmin() ? '/usage-stats' : '/dashboard';
        });
        // webミドルウェアグループにパスワード変更チェックを追加
        $middleware->web(append: [
            \App\Http\Middleware\CheckIpRestriction::class,
            \App\Http\Middleware\CheckPasswordChange::class,
            \App\Http\Middleware\LogAccess::class,
        ]);
        // ミドルウェアエイリアス
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'admin-only' => \App\Http\Middleware\AdminOnlyMiddleware::class,
            'system-admin-only' => \App\Http\Middleware\SystemAdminOnlyMiddleware::class,
            'practitioners' => \App\Http\Middleware\PractitionersMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // 毎日午前2時に未ログイントレーナーを自動ロック
        $schedule->command('trainers:lock-inactive')->dailyAt('02:00');
        // 毎日午前3時に保存期間を過ぎた音声ファイルを自動削除
        $schedule->command('audio-records:delete-expired --days=7')->dailyAt('03:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
