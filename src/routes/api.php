<?php

use App\Http\Controllers\AudioRecordController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TrainingRecordController;
use App\Http\Controllers\TrainerController;
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
    Route::get('/trainers', [TrainerController::class, 'apiList'])->name('api.trainers.list');
    Route::post('/training-records/auto-create', [TrainingRecordController::class, 'autoCreate'])->name('api.training-records.auto-create');
});
