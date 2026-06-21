<?php

use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\AutoLogoutController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ConsultationTypeController;
use App\Http\Controllers\CounselingRecordController;
use App\Http\Controllers\CounselorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IpRestrictionController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupportStatusController;
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
| 認証不要のルート
|--------------------------------------------------------------------------
*/

// ルートURLはログイン画面にリダイレクト
Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// クライアント事前入力（公開URL、認証不要）
Route::get('client-intake/token/{token}', [ClientIntakeController::class, 'showByToken'])->name('client-intake.show-by-token');
Route::post('client-intake/token/{token}', [ClientIntakeController::class, 'storeByToken'])->name('client-intake.store-by-token');

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
    | 実務カウンセラー（管理カウンセラー + 一般カウンセラー）共通ルート
    | クライアントの個人情報・相談内容を扱うため、system_admin は 403 で弾く
    |--------------------------------------------------------------------------
    */
    Route::middleware('practitioners')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('clients', ClientController::class);
        Route::resource('counseling-records', CounselingRecordController::class);

        // 旧録音画面 → 録音【改良版】にリダイレクト
        Route::get('recording', function () {
            return redirect()->route('recording-v2.index');
        })->name('recording');

        // 録音【改良版】（全カウンセラーがアクセス可能）
        Route::prefix('recording-v2')->name('recording-v2.')->group(function () {
            Route::get('/', [RecordingV2Controller::class, 'index'])->name('index');
            Route::post('/start', [RecordingV2Controller::class, 'start'])->name('start');
            Route::get('/session', [RecordingV2Controller::class, 'session'])->name('session');
        });

        // 事前入力URL管理（カウンセラー向け）
        Route::get('client-intake-tokens', [ClientIntakeTokenController::class, 'index'])->name('client-intake-tokens.index');
        Route::post('client-intake-tokens', [ClientIntakeTokenController::class, 'store'])->name('client-intake-tokens.store');
        Route::delete('client-intake-tokens/{id}', [ClientIntakeTokenController::class, 'destroy'])->name('client-intake-tokens.destroy');

        // 音声管理（全カウンセラーがアクセス可能）
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
        // 自動ログアウト設定
        Route::get('settings/auto-logout', [AutoLogoutController::class, 'edit'])->name('settings.auto-logout.edit');
        Route::put('settings/auto-logout', [AutoLogoutController::class, 'update'])->name('settings.auto-logout.update');

        // 要約プロンプト設定
        Route::get('settings/summary-prompts', [SummaryPromptController::class, 'edit'])->name('settings.summary-prompts.edit');
        Route::put('settings/summary-prompts', [SummaryPromptController::class, 'update'])->name('settings.summary-prompts.update');

        // カウンセラーアカウント管理
        Route::resource('counselors', CounselorController::class)->except(['show']);
        Route::get('counselors/{counselor}/reset-password', [CounselorController::class, 'showResetPassword'])->name('counselors.reset-password');
        Route::put('counselors/{counselor}/reset-password', [CounselorController::class, 'resetPassword'])->name('counselors.reset-password.update');
        Route::patch('counselors/{counselor}/unlock', [CounselorController::class, 'unlock'])->name('counselors.unlock');
        Route::patch('counselors/{counselor}/toggle-active', [CounselorController::class, 'toggleActive'])->name('counselors.toggle-active');
        Route::patch('counselors/{counselor}/move-up', [CounselorController::class, 'moveUp'])->name('counselors.move-up');
        Route::patch('counselors/{counselor}/move-down', [CounselorController::class, 'moveDown'])->name('counselors.move-down');

        // カウンセラー操作履歴
        Route::get('access-logs', [AccessLogController::class, 'index'])->name('access-logs.index');

        // マスタ管理
        Route::prefix('master')->name('master.')->group(function () {
            // 相談内容マスタ
            Route::get('consultation-types', [ConsultationTypeController::class, 'index'])->name('consultation-types.index');
            Route::post('consultation-types', [ConsultationTypeController::class, 'store'])->name('consultation-types.store');
            Route::put('consultation-types/{consultationType}', [ConsultationTypeController::class, 'update'])->name('consultation-types.update');
            Route::delete('consultation-types/{consultationType}', [ConsultationTypeController::class, 'destroy'])->name('consultation-types.destroy');
            Route::patch('consultation-types/{consultationType}/move-up', [ConsultationTypeController::class, 'moveUp'])->name('consultation-types.move-up');
            Route::patch('consultation-types/{consultationType}/move-down', [ConsultationTypeController::class, 'moveDown'])->name('consultation-types.move-down');

            // フェーズマスタ
            Route::get('phases', [PhaseController::class, 'index'])->name('phases.index');
            Route::post('phases', [PhaseController::class, 'store'])->name('phases.store');
            Route::put('phases/{phase}', [PhaseController::class, 'update'])->name('phases.update');
            Route::delete('phases/{phase}', [PhaseController::class, 'destroy'])->name('phases.destroy');
            Route::patch('phases/{phase}/move-up', [PhaseController::class, 'moveUp'])->name('phases.move-up');
            Route::patch('phases/{phase}/move-down', [PhaseController::class, 'moveDown'])->name('phases.move-down');

            // 支援状態マスタ
            Route::get('support-statuses', [SupportStatusController::class, 'index'])->name('support-statuses.index');
            Route::post('support-statuses', [SupportStatusController::class, 'store'])->name('support-statuses.store');
            Route::put('support-statuses/{supportStatus}', [SupportStatusController::class, 'update'])->name('support-statuses.update');
            Route::delete('support-statuses/{supportStatus}', [SupportStatusController::class, 'destroy'])->name('support-statuses.destroy');
            Route::patch('support-statuses/{supportStatus}/move-up', [SupportStatusController::class, 'moveUp'])->name('support-statuses.move-up');
            Route::patch('support-statuses/{supportStatus}/move-down', [SupportStatusController::class, 'moveDown'])->name('support-statuses.move-down');
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
