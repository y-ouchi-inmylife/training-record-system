<?php

use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\LoginController as ClientLoginController;
use App\Http\Controllers\Client\LogoutController as ClientLogoutController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientViewReleaseController;
use App\Http\Controllers\ClientViewRevokeController;
use App\Http\Controllers\TrainingTypeController;
use App\Http\Controllers\TrainingRecordController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IpRestrictionController;
use App\Http\Controllers\MediaRecordController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AudioRecordController;
use App\Http\Controllers\ClientIntakeController;
use App\Http\Controllers\ClientIntakeTokenController;
use App\Http\Controllers\RecordingV2Controller;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\SummaryPromptController;
use App\Http\Controllers\UsageStatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| トレーナー用サブドメイン（内部）
|--------------------------------------------------------------------------
| トレーナー側の全ルートを Route::domain で囲む。ローカルは両ホストが localhost
| に解決されるため、既存の動作は変わらない。
*/
Route::domain(config('subdomain.trainer_host'))->middleware('check-ip')->group(function () {

    // ルートURLはログイン画面にリダイレクト
    Route::redirect('/', '/login');

    /*
    |--------------------------------------------------------------------------
    | 認証不要のルート（トレーナー用ログイン）
    |--------------------------------------------------------------------------
    */
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);
    });

    /*
    |--------------------------------------------------------------------------
    | 認証が必要なルート
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

        // パスワード変更（初回ログイン時の強制変更対応）
        Route::get('/password/change', [ChangePasswordController::class, 'show'])->name('password.change');
        Route::post('/password/change', [ChangePasswordController::class, 'update'])->name('password.update');

        // マイプロフィール
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        /*
        |--------------------------------------------------------------------------
        | 実務トレーナー（管理トレーナー + 一般トレーナー）共通ルート
        | クライアントの個人情報・トレーニング内容を扱うため、system_admin は 403 で弾く
        |--------------------------------------------------------------------------
        */
        Route::middleware('practitioners')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::resource('clients', ClientController::class);
            // クライアント閲覧解放（柱2）— Route::resource の外に個別追加
            Route::post('clients/{client}/release-view', [ClientViewReleaseController::class, 'store'])
                ->name('client-view-release.store');
            Route::post('clients/{client}/revoke-view', [ClientViewRevokeController::class, 'store'])
                ->name('client-view-revoke.store');
            Route::resource('training-records', TrainingRecordController::class);

            // 旧録音画面 → 録音【改良版】にリダイレクト
            Route::get('recording', function () {
                return redirect()->route('recording-v2.index');
            })->name('recording');

            // 録音【改良版】（全トレーナーがアクセス可能）
            Route::prefix('recording-v2')->name('recording-v2.')->group(function () {
                Route::get('/', [RecordingV2Controller::class, 'index'])->name('index');
                Route::post('/start', [RecordingV2Controller::class, 'start'])->name('start');
                Route::get('/session', [RecordingV2Controller::class, 'session'])->name('session');
            });

            // 事前入力URL発行/削除（S-0305 クライアント詳細から起動）
            Route::post('clients/{client}/intake-tokens', [ClientIntakeTokenController::class, 'store'])->name('client-intake-tokens.store');
            Route::delete('clients/{client}/intake-tokens/{tokenId}', [ClientIntakeTokenController::class, 'destroy'])->name('client-intake-tokens.destroy');

            // メディア管理（全トレーナーがアクセス可能）
            // 一覧・登録・詳細モーダル経由の更新/削除。再生(play)は api.php 側。
            Route::get('media-records', [MediaRecordController::class, 'index'])->name('media-records.index');
            Route::put('media-records/{mediaRecord}', [MediaRecordController::class, 'update'])->name('media-records.update');
            Route::delete('media-records/{mediaRecord}', [MediaRecordController::class, 'destroy'])->name('media-records.destroy');

            // 音声管理（全トレーナーがアクセス可能）
            Route::get('audio-records', [AudioRecordController::class, 'index'])->name('audio-records.index');
            Route::post('audio-records/upload', [AudioRecordController::class, 'uploadStore'])->name('audio-records.upload.store');
            Route::post('audio-records/recording', [AudioRecordController::class, 'recordingStore'])->name('audio-records.recording.store');
            Route::get('audio-records/upload/create', [AudioRecordController::class, 'uploadCreate'])->name('audio-records.upload.create');
            Route::get('audio-records/text-paste/create', [AudioRecordController::class, 'textPasteCreate'])->name('audio-records.text-paste.create');
            Route::post('audio-records/text-paste', [AudioRecordController::class, 'textPasteStore'])->name('audio-records.text-paste.store');
            Route::get('audio-records/{audioRecord}', [AudioRecordController::class, 'show'])->name('audio-records.show');
            Route::put('audio-records/{audioRecord}', [AudioRecordController::class, 'update'])->name('audio-records.update');
            Route::delete('audio-records/{audioRecord}', [AudioRecordController::class, 'destroy'])->name('audio-records.destroy');
            Route::delete('audio-records/{audioRecord}/delete-audio', [AudioRecordController::class, 'deleteAudioOnly'])->name('audio-records.delete-audio');
            Route::get('audio-records/{audioRecord}/play', [AudioRecordController::class, 'play'])->name('audio-records.play');

            // レポート
            Route::get('statistics/clients', [StatisticsController::class, 'clients'])->name('statistics.clients');
        });

        /*
        |--------------------------------------------------------------------------
        | 管理者専用ルート（adminロールのみ、system_adminはアクセス不可）
        |--------------------------------------------------------------------------
        */
        Route::middleware('admin-only')->group(function () {
            // 要約プロンプト設定
            Route::get('settings/summary-prompts', [SummaryPromptController::class, 'edit'])->name('settings.summary-prompts.edit');
            Route::put('settings/summary-prompts', [SummaryPromptController::class, 'update'])->name('settings.summary-prompts.update');

            // トレーナーアカウント管理
            Route::resource('trainers', TrainerController::class)->except(['show']);
            Route::get('trainers/{trainer}/reset-password', [TrainerController::class, 'showResetPassword'])->name('trainers.reset-password');
            Route::put('trainers/{trainer}/reset-password', [TrainerController::class, 'resetPassword'])->name('trainers.reset-password.update');
            Route::patch('trainers/{trainer}/unlock', [TrainerController::class, 'unlock'])->name('trainers.unlock');
            Route::patch('trainers/{trainer}/toggle-active', [TrainerController::class, 'toggleActive'])->name('trainers.toggle-active');
            Route::patch('trainers/{trainer}/move-up', [TrainerController::class, 'moveUp'])->name('trainers.move-up');
            Route::patch('trainers/{trainer}/move-down', [TrainerController::class, 'moveDown'])->name('trainers.move-down');

            // トレーナー操作履歴
            Route::get('access-logs', [AccessLogController::class, 'index'])->name('access-logs.index');

            // マスタ管理
            Route::prefix('master')->name('master.')->group(function () {
                // トレーニング内容マスタ
                Route::get('training-types', [TrainingTypeController::class, 'index'])->name('training-types.index');
                Route::post('training-types', [TrainingTypeController::class, 'store'])->name('training-types.store');
                Route::put('training-types/{trainingType}', [TrainingTypeController::class, 'update'])->name('training-types.update');
                Route::delete('training-types/{trainingType}', [TrainingTypeController::class, 'destroy'])->name('training-types.destroy');
                Route::patch('training-types/{trainingType}/move-up', [TrainingTypeController::class, 'moveUp'])->name('training-types.move-up');
                Route::patch('training-types/{trainingType}/move-down', [TrainingTypeController::class, 'moveDown'])->name('training-types.move-down');

                // フェーズマスタ
                Route::get('phases', [PhaseController::class, 'index'])->name('phases.index');
                Route::post('phases', [PhaseController::class, 'store'])->name('phases.store');
                Route::put('phases/{phase}', [PhaseController::class, 'update'])->name('phases.update');
                Route::delete('phases/{phase}', [PhaseController::class, 'destroy'])->name('phases.destroy');
                Route::patch('phases/{phase}/move-up', [PhaseController::class, 'moveUp'])->name('phases.move-up');
                Route::patch('phases/{phase}/move-down', [PhaseController::class, 'moveDown'])->name('phases.move-down');

            });
        });

        /*
        |--------------------------------------------------------------------------
        | システム管理者専用ルート（system_adminロールのみ）
        |--------------------------------------------------------------------------
        */
        Route::middleware('system-admin-only')->group(function () {
            // 音声データ管理状況（音声ファイル一覧）
            Route::get('usage-stats', [UsageStatsController::class, 'index'])->name('usage-stats.index');

            // IPアドレス制限設定
            Route::get('settings/ip-restriction', [IpRestrictionController::class, 'edit'])->name('settings.ip-restriction.edit');
            Route::put('settings/ip-restriction', [IpRestrictionController::class, 'update'])->name('settings.ip-restriction.update');
        });
    });

});

/*
|--------------------------------------------------------------------------
| クライアント用サブドメイン（外部）
|--------------------------------------------------------------------------
| クライアント側の全ルートを Route::domain で囲む。ローカルは両ホストが localhost
| に解決されるため、既存の動作は変わらない。
| ルート名は変えないため、route() 呼び出しや CheckIpRestriction のバイパス判定
| （routeIs('client-intake.*') / routeIs('client-portal.*')）は無改修で動く。
*/
Route::domain(config('subdomain.client_host'))->group(function () {

    // クライアント用ドメインの / → クライアントログインへ（トレーナー側と対称）
    Route::redirect('/', '/client-portal/login');

    // --- 公開（認証不要） ---

    // クライアント事前入力（公開URL、認証不要）
    Route::get('client-intake/token/{token}', [ClientIntakeController::class, 'showByToken'])->name('client-intake.show-by-token');
    Route::put('client-intake/token/{token}', [ClientIntakeController::class, 'updateByToken'])->name('client-intake.update-by-token');

    // クライアントパスワード設定(柱2 塊D 段2、公開URL、認証不要)。
    // /client-portal/* の auth:client グループには入れず、client-intake と同じ公開領域に置く。
    Route::get('client-portal/password-setup/{token}', [\App\Http\Controllers\Client\PasswordSetupController::class, 'showByToken'])
        ->name('client-portal.password-setup.show');
    Route::post('client-portal/password-setup/{token}', [\App\Http\Controllers\Client\PasswordSetupController::class, 'storeByToken'])
        ->name('client-portal.password-setup.store');

    /*
    |--------------------------------------------------------------------------
    | クライアント閲覧機能（柱2）
    |--------------------------------------------------------------------------
    | トレーナー向け（web guard）とは独立し、client guard で認証する。
    | practitioners 等トレーナー専用ミドルウェアは絶対に付けない（隔離）。
    */
    Route::prefix('client-portal')->name('client-portal.')->group(function () {
        // 未認証向け（guest:client）：ログイン画面・ログイン処理
        // ログイン済みは redirectUsersTo により /client-portal/dashboard へ振り分けられる。
        Route::middleware('guest:client')->group(function () {
            Route::get('/login', [ClientLoginController::class, 'showLoginForm'])->name('login');
            Route::post('/login', [ClientLoginController::class, 'login']);
        });

        // 認証必須（auth:client）。未認証は redirectGuestsTo により /client-portal/login へ振り分けられる。
        Route::middleware('auth:client')->group(function () {
            Route::post('/logout', [ClientLogoutController::class, 'logout'])->name('logout');
            Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
            Route::get('/training-records/{trainingRecord}', [\App\Http\Controllers\Client\TrainingRecordController::class, 'show'])
                ->name('training-records.show');
            Route::get('/media/{mediaRecord}/play', [\App\Http\Controllers\Client\MediaRecordController::class, 'play'])
                ->name('media.play');
        });
    });

});
