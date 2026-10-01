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
    // GET is accepted too, so firmware that used to call a local XAMPP script with ?tag=…&weight=… only needs a new URL.
    Route::match(['get', 'post'], '/scans', [\App\Http\Controllers\Api\DeviceController::class, 'scans'])->name('api.v1.scans');
    Route::match(['get', 'post'], '/readings', [\App\Http\Controllers\Api\ReadingController::class, 'store'])->name('api.v1.readings');
    Route::post('/pair', [\App\Http\Controllers\Api\PairingController::class, 'start'])->middleware('throttle:10,1')->name('api.v1.pair');
    Route::post('/pair/status', [\App\Http\Controllers\Api\PairingController::class, 'status'])->name('api.v1.pair.status');
});
