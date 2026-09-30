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

// Device API v1 — see App\Http\Controllers\Api\DeviceController for formats.
Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
    Route::get('/ping', [\App\Http\Controllers\Api\DeviceController::class, 'ping'])->name('api.v1.ping');
    Route::post('/scans', [\App\Http\Controllers\Api\DeviceController::class, 'scans'])->name('api.v1.scans');
});
