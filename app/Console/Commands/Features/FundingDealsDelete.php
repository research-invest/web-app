<?php

/**
 * php artisan funding-deals-old-delete:run
 */

namespace App\Console\Commands\Features;

use App\Jobs\FundingTrade;
use App\Models\Funding\FundingDeal;
use Illuminate\Console\Command;
use  \Illuminate\Database\Eloquent\Collection;

class FundingDealsDelete extends Command
{
    protected $signature = 'funding-deals-old-delete:run';
    protected $description = '';

    public function handle()
    {
        $timeStart = microtime(true);

        $this->process();

        $this->info('Использовано памяти: ' . (memory_get_peak_usage() / 1024 / 1024) . " MB");
        $this->info('Время выполнения в секундах: ' . ((microtime(true) - $timeStart)));
    }

    private function process(): void
    {
        FundingDeal::query()
            ->where('run_time', '<', now())
            ->new()
            ->update([
                'status' => FundingDeal::STATUS_ERROR,
                'error' => 'Уже прошло время фандинга',
            ]);
    }

}
