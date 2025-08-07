<?php

use App\Http\Controllers\Api\BookOscillatorController;
use App\Http\Controllers\Api\WatchController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FundingController;
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
});

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
