<?php

use App\Http\Controllers\Api\PipelineWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/pipeline/webhook', [PipelineWebhookController::class, 'store'])
    ->middleware('pipeline.secret')
    ->name('api.pipeline.webhook');
