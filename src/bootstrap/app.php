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
        // 未認証時のリダイレクト先
        $middleware->redirectGuestsTo('/login');
        // 認証済みユーザーがguestルートにアクセスした場合のリダイレクト先
        // システム管理者は音声ファイル一覧（S-0701）、それ以外はダッシュボード（S-0101）へ
        $middleware->redirectUsersTo(fn ($request) => $request->user()?->isSystemAdmin() ? '/usage-stats' : '/dashboard');
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
        $schedule->command('counselors:lock-inactive')->dailyAt('02:00');
        // 毎日午前3時に保存期間を過ぎた音声ファイルを自動削除
        $schedule->command('audio-records:delete-expired --days=7')->dailyAt('03:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
