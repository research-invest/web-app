<?php

use App\Http\Controllers\Api\WatchController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

//->middleware('auth:api')
Route::prefix('v1')->group(function () {
    Route::get('/watch', [WatchController::class, 'index']);

    Route::get('/watch/trades', [WatchController::class, 'trades']);
    Route::post('/watch/trades/{trade}/cancel', [WatchController::class, 'cancelTrade']);

});

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
