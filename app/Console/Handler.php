<?php

namespace App\Console;

use App\Console\Commands\Alerts\CheckFavoritePairs;
use App\Console\Commands\Alerts\CheckLiquidationWarnings;
use App\Console\Commands\Alerts\CheckTradeLevels;
use App\Console\Commands\Alerts\FreeSpaceAlert;
use App\Console\Commands\Alerts\SendSmartMoneyAlert;
use App\Console\Commands\Alerts\SendTradePnLNotification;
use App\Console\Commands\AnalyzeTopPerformingCoinSnapshots;
use App\Console\Commands\CollectTopPerformingCoinSnapshots;
use App\Console\Commands\SendIndexChart;
use App\Console\Commands\UpdateCurrencies;
use App\Console\Commands\UpdateTradesPnL;
use Illuminate\Console\Scheduling\Schedule;

class Handler
{
    public function __invoke(Schedule $schedule): void
    {
        $schedule->command(UpdateCurrencies::class)
            ->withoutOverlapping()
            ->everyMinute();

        $schedule->command(UpdateTradesPnL::class)
            ->withoutOverlapping()
            ->everyTwoMinutes();

//        $schedule->command(CheckTradeLevels::class)->everyTwoMinutes();
        $schedule->command(SendTradePnLNotification::class)->everyTenMinutes();
        $schedule->command(CheckLiquidationWarnings::class)->everyFiveMinutes();
        $schedule->command(FreeSpaceAlert::class)->hourly();
        $schedule->command(SendSmartMoneyAlert::class)->everyThirtyMinutes();
//        $schedule->command(SendIndexChart::class)->everyThreeMinutes();
//        $schedule->command(CheckFavoritePairs::class)->everyThirtyMinutes();

        $schedule->command(CollectTopPerformingCoinSnapshots::class)
            ->everyTenMinutes()
            ->withoutOverlapping();

//        $schedule->command(AnalyzeTopPerformingCoinSnapshots::class)
//            ->everyThirtyMinutes()
//            ->withoutOverlapping();
    }
}
