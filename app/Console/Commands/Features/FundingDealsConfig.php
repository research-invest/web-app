<?php

/**
 * php artisan funding-deals-config:run
 */

namespace App\Console\Commands\Features;

use App\Models\Currency;
use App\Models\Funding\FundingDeal;
use App\Models\Funding\FundingDealConfig;
use Illuminate\Console\Command;
use  \Illuminate\Database\Eloquent\Collection;

class FundingDealsConfig extends Command
{
    protected $signature = 'funding-deals-config:run';
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
        $configs = $this->getConfigs();

        foreach ($configs as $config) {
            $user = $config->user;

            if (!$user) {
                continue;
            }

            $currencies = $this->getCurrency($config);

            foreach ($currencies as $currency) {

                $existDeal = $config->user->fundingDeals()
                    ->new()
                    ->where('currency_id', $currency->id)
                    ->exists();

                if ($existDeal) {
                    continue;
                }

                /**
                 * @var FundingDeal $deal
                 */
                $deal = $config->deals()->create([
                    'user_id' => $config->user->id,
                    'currency_id' => $currency->id,
                    'funding_time' => $currency->next_settle_time->timestamp,
                    'run_time' => $currency->next_settle_time->copy()->subSeconds(1),
                    'funding_rate' => $currency->funding_rate,
                    'status' => FundingDeal::STATUS_NEW,
                    'leverage' => $config->leverage,
                    'position_size' => $config->position_size,
                    'price_history' => [],
                ]);

//                FundingTrade::dispatch($deal)
//                    ->delay($deal->run_time);
            }
        }

        return 0;
    }

    /**
     * @return FundingDealConfig[]
     */
    private function getConfigs(): Collection
    {
        return FundingDealConfig::query()
            ->isActive()
            ->get();
    }

    /**
     * @param FundingDealConfig $config
     * @return Currency[]|Collection
     */
    private function getCurrency(FundingDealConfig $config): Collection
    {
        return Currency::query()
            ->isActive()
            ->where('exchange', $config->exchange)
            ->where('funding_rate', '<=', $config->min_funding_rate)
            ->get();
    }
}
