<?php

namespace App\Console;

use App\Console\Commands\UpdateCurrencies;
use App\Console\Commands\UpdateTradesPnL;
use Illuminate\Console\Scheduling\Schedule;

class Handler
{
    public function __invoke(Schedule $schedule): void
    {
        $schedule->command(UpdateCurrencies::class)->everyTwoMinutes();
        $schedule->command(UpdateTradesPnL::class)->everyTwoMinutes();

    }
}
