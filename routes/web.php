<?php

use App\Http\Controllers\TradeOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::delete('trading/deals/{trade}/orders/{order}', [TradeOrderController::class, 'delete'])
    ->name('platform.trading.deal.orders.remove');
