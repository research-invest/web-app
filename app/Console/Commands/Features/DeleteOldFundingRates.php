<?php

/**
 * php artisan funding:clear
 */

namespace App\Console\Commands\Features;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteOldFundingRates extends Command
{
    protected $signature = 'funding:clear';
    protected $description = '';

    public function handle()
    {
        $timeStart = microtime(true);

        $this->clear();

        $this->info('Использовано памяти: ' . (memory_get_peak_usage() / 1024 / 1024) . " MB");
        $this->info('Время выполнения в секундах: ' . ((microtime(true) - $timeStart)));
    }

    private function clear(): void
    {
        do {
            $result = DB::table('funding_rates')
                ->where('created_at', '<', now()->subDays(5))
                ->limit(1000)
                ->delete();

            $this->comment('1000 done');

        } while ($result > 0);
    }


}
