<?php

use App\Http\Controllers\Api\PipelineWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/pipeline/webhook', [PipelineWebhookController::class, 'store'])
    ->middleware('pipeline.secret')
    ->name('api.pipeline.webhook');

// RFID readers push scans here with their per-device token (Authorization: Bearer <token>).
Route::post('/reader/sync', [\App\Http\Controllers\Api\ReaderSyncController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('api.reader.sync');
