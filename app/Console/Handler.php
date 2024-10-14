<?php

namespace App\Console;

use App\Console\Commands\SyncTrxWallets;
use Illuminate\Console\Scheduling\Schedule;

class Handler
{
    public function __invoke(Schedule $schedule): void
    {
        $schedule->command(SyncTrxWallets::class)->hourly();

    }
}
