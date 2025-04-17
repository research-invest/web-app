<?php
/**
 * php artisan wallets:update-balances
 */

namespace App\Console\Commands\BtcWallets;

use App\Helpers\MathHelper;
use App\Models\BtcWallets\Wallet;
use App\Models\BtcWallets\WalletBalance;
use App\Services\BlockonomicsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateWalletBalances extends Command
{
    protected $signature = 'wallets:update-balances';
    protected $description = 'Обновляет балансы всех кошельков';

    private BlockonomicsService $blockonomicsService;

    public function __construct(BlockonomicsService $blockonomicsService)
    {
        parent::__construct();
        $this->blockonomicsService = $blockonomicsService;
    }

    public function handle()
    {
        $timeStart = microtime(true);

        $this->process();

        $this->info('Использовано памяти: ' . (memory_get_peak_usage() / 1024 / 1024) . " MB");
        $this->info('Время выполнения в секундах: ' . ((microtime(true) - $timeStart)));
    }

    private function process()
    {
        $this->info('Начинаем обновление балансов...');

        try {
            // Получаем все кошельки
            Wallet::chunk(50, function ($wallets) {
                $addresses = $wallets->pluck('address')->toArray();

                $this->info('Обработка ' . count($addresses) . ' адресов...');

                try {
                    $balances = $this->blockonomicsService->getBalances($addresses);

                    foreach ($wallets as $wallet) {
                        if (isset($balances[$wallet->address])) {
                            $balance = $balances[$wallet->address] ?? 0;

                            WalletBalance::create([
                                'wallet_id' => $wallet->id,
                                'balance' => $balance
                            ]);

                            $wallet->update([
                                'balance' => $balance,
                                'diff_percent' => MathHelper::getPercentOfNumber($wallet->balance, $balance),
                            ]);
                        }
                    }

                    $this->info('Пакет обработан успешно');
                } catch (\Exception $e) {
                    $this->error('Ошибка при обработке пакета: ' . $e->getMessage());
                    Log::error('Ошибка обновления балансов: ' . $e->getMessage());
                }

                sleep(3);
            });

            $this->info('Обновление балансов завершено');
        } catch (\Exception $e) {
            $this->error('Критическая ошибка: ' . $e->getMessage());
            Log::error('Критическая ошибка обновления балансов: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
