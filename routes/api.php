<?php

use App\Http\Controllers\Api\WatchController;
use App\Http\Controllers\TradeOrderController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    Route::get('/watch', [WatchController::class, 'index']);

    Route::get('/watch/trades', [WatchController::class, 'trades']);
    Route::post('/watch/trades/{trade}/cancel', [WatchController::class, 'cancelTrade']);
});
