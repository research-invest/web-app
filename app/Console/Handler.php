<?php

namespace App\Console;

use App\Console\Commands\Alerts\CheckLiquidationWarnings;
use App\Console\Commands\Alerts\CheckTradeLevels;
use App\Console\Commands\Alerts\FreeSpaceAlert;
use App\Console\Commands\Alerts\SendTradePnLNotification;
use App\Console\Commands\SendIndexChart;
use App\Console\Commands\UpdateCurrencies;
use App\Console\Commands\UpdateTradesPnL;
use Illuminate\Console\Scheduling\Schedule;

class Handler
{
    public function __invoke(Schedule $schedule): void
    {
        $schedule->command(UpdateCurrencies::class)->everyTwoMinutes();
        $schedule->command(UpdateTradesPnL::class)->everyTwoMinutes();
        $schedule->command(CheckTradeLevels::class)->everyTwoMinutes();
        $schedule->command(SendTradePnLNotification::class)->everyTenMinutes();
        $schedule->command(CheckLiquidationWarnings::class)->everyFiveMinutes();
        $schedule->command(FreeSpaceAlert::class)->hourly();
//        $schedule->command(SendIndexChart::class)->everyThreeMinutes();
    }
}
