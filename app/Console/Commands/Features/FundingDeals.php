<?php

/**
 * php artisan funding-deals:run
 */

namespace App\Console\Commands\Features;

use App\Jobs\FundingTrade;
use App\Models\Funding\FundingDeal;
use Illuminate\Console\Command;
use  \Illuminate\Database\Eloquent\Collection;

/**
 * @deprecated
 */
class FundingDeals extends Command
{
    protected $signature = 'funding-deals:run';
    protected $description = '';

    public function handle()
    {
        $timeStart = microtime(true);

        $this->process();

        $this->info('Использовано памяти: ' . (memory_get_peak_usage() / 1024 / 1024) . " MB");
        $this->info('Время выполнения в секундах: ' . ((microtime(true) - $timeStart)));
    }

    private function process(): int
    {
        foreach ($this->getDeals() as $deal) {
            $user = $deal->user;

            if (!$user) {
                continue;
            }

//            FundingTrade::dispatchSync($deal);
            FundingTrade::dispatch($deal);
        }

        return 0;
    }

    /**
     * @return FundingDeal[]
     */
    private function getDeals(): Collection
    {
        return FundingDeal::query()
            ->where('run_time', '<=', now())
            ->new()
            ->get();
    }
}
