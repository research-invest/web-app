<?php

use App\Http\Controllers\Api\BookOscillatorController;
use App\Http\Controllers\Api\WatchController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FundingController;
use App\Http\Controllers\Api\TradingViewWebhookController;
use Illuminate\Support\Facades\Route;


// Маршруты, требующие API ключ
Route::prefix('v1')->group(function () {
    Route::middleware('auth:api')->prefix('watch')->group(function () {
        Route::get('/', [WatchController::class, 'index']);
        Route::get('/trades', [WatchController::class, 'trades']);
        Route::post('/trades/{trade}/cancel', [WatchController::class, 'cancelTrade']);
    });

    Route::middleware('api.key')->prefix('funding')->group(function () {
        Route::get('/configs', [FundingController::class, 'getConfigs']);
        Route::get('/deals', [FundingController::class, 'getDeals']);
        Route::post('/deals/update-result', [FundingController::class, 'updateDealResult']);
    });


    //api/v1/oscillator
    Route::get('/oscillator', [BookOscillatorController::class, 'get']);

    // TradingView Webhooks - публичные маршруты
    //selll.ru/api/v1/tradingview/webhook

    Route::prefix('tradingview')->group(function () {
        Route::post('/webhook', [TradingViewWebhookController::class, 'receive'])->name('api.tradingview.webhook');
        Route::get('/test', [TradingViewWebhookController::class, 'test'])->name('api.tradingview.test');
    });

    // TradingView Webhooks - защищенные маршруты (требуют аутентификации)
    Route::middleware('api.key')->prefix('tradingview')->group(function () {
        Route::get('/webhooks', [TradingViewWebhookController::class, 'index'])->name('api.tradingview.webhooks.index');
        Route::get('/webhooks/{webhook}', [TradingViewWebhookController::class, 'show'])->name('api.tradingview.webhooks.show');
    });
});

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
