<?php

use App\Http\Controllers\PuppeteerController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::get('/example', function () {
        return ['message' => 'API is working'];
    });
});

Route::post('/telegram/{token}/webhook', WebhookController::class);
Route::post('/puppeteer/callback', [PuppeteerController::class, 'callback']);
