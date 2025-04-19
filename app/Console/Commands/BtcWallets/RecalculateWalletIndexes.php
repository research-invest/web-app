<?php
/**
 * php artisan calculate:wallet-metrics
 */

namespace App\Console\Commands\BtcWallets;

use App\Models\BtcWallets\Wallet;
use App\Models\BtcWallets\WalletMetric;
use App\Services\Analyze\BtcWalletAnalysis;
use Illuminate\Console\Command;

class RecalculateWalletIndexes extends Command
{

    protected $signature = 'calculate:wallet-metrics';
    protected $description = 'Calculate and store metrics for all wallets';

    public function handle(): void
    {
        Wallet::chunk(100, function ($wallets) {
            foreach ($wallets as $wallet) {
                $metrics = (new BtcWalletAnalysis($wallet))->calculate();

                WalletMetric::create(['wallet_id' => $wallet->id, ...$metrics]);

                $this->info("✅ Wallet {$wallet->id} metrics calculated.");
            }
        });
    }

}
