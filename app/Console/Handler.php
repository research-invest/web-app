<?php

namespace App\Console;

use App\Console\Commands\UpdateCurrencies;
use Illuminate\Console\Scheduling\Schedule;

class Handler
{
    public function __invoke(Schedule $schedule): void
    {
        $schedule->command(UpdateCurrencies::class)->everyTenMinutes();

    }
}
