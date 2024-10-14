<?php
/**
 * php artisan for-test:run
 */

namespace App\Console\Commands;

use App\Services\Api\Currencies;
use App\Services\Api\LatestPrice;
use App\Services\Api\Tickers;
use Illuminate\Console\Command;

class forTest extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'for-test:run';

    /**
     * The console command description.
     *
     * @var string
     */

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        $this->testLatest();
//        $this->getTickers();
        $this->getCurrencies();
    }

    private function testLatest()
    {
        $result = (new LatestPrice())->getLatestPrices(['ethusdt', 'taousdt']);

        dd($result);
    }

    private function getTickers()
    {
        $result = (new Tickers())->getTickers('TAOUSDT', 60);

        dd($result);
    }

    private function getCurrencies()
    {
        $result = (new Currencies())->getCurrencies();

        dd($result);
    }

}
