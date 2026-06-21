<?php

use App\Http\Controllers\AudioRecordController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CounselingRecordController;
use App\Http\Controllers\CounselorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'practitioners'])->group(function () {
    Route::get('/clients/search', [ClientController::class, 'apiSearch'])->name('api.clients.search');
    Route::get('/audio-records/summaries', [AudioRecordController::class, 'summaries'])->name('api.audio-records.summaries');
    Route::get('/audio-records/{audioRecord}/summary', [AudioRecordController::class, 'getSummary'])->name('api.audio-records.summary');
    Route::post('/audio-records/{audioRecord}/transcribe', [AudioRecordController::class, 'transcribe'])->name('api.audio-records.transcribe');
    Route::post('/audio-records/{audioRecord}/summarize', [AudioRecordController::class, 'summarize'])->name('api.audio-records.summarize');
    Route::get('/counselors', [CounselorController::class, 'apiList'])->name('api.counselors.list');
    Route::post('/counseling-records/auto-create', [CounselingRecordController::class, 'autoCreate'])->name('api.counseling-records.auto-create');
});
