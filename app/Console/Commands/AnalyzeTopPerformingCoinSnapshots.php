<?php

/**
 * php artisan analyze-top-performing-coin-snapshots:run
 */
namespace App\Console\Commands;

use App\Helpers\ArrayHelper;
use App\Models\TopPerformingCoinSnapshot;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AnalyzeTopPerformingCoinSnapshots extends Command
{
    protected $signature = 'analyze-top-performing-coin-snapshots:run';

    protected $description = '';

    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    public function handle(): void
    {
        try {
//            $periodMinutes = (int)$this->option('period');
//            $volumeMin = (int)$this->option('volume-min');
//            $priceMax = (int)$this->option('price-max');
            $periodMinutes = 30;
            $volumeMin = 20;
            $priceMax = 20;

            $startTime = Carbon::now()->subMinutes($periodMinutes);

            $topCoins = TopPerformingCoinSnapshot::query()
                ->with('currency')
                ->where('created_at', '>=', $startTime)
//                ->whereHas('currency', function ($query) {
//                    $query->where('is_active', true);
//                })
                ->where('volume_diff_percent', '>=', $volumeMin)
                ->whereBetween('price_change_percent', [-$priceMax, $priceMax])
                ->orderByDesc('volume_diff_percent')
                ->limit(10)
                ->get();

            if ($topCoins->isEmpty()) {
                $this->info('Не найдены подходящие валюты');
                return;
            }

            $message = "🔍 *Найдены монеты с высоким объемом и стабильной ценой:*\n\n";

            foreach ($topCoins as $coin) {
                $message .= sprintf(
                    "🪙 *%s*\n" .
                    "💹 Изменение объема: *%+.1f%%*\n" .
                    "📊 Изменение цены: *%+.1f%%*\n" .
                    "⏰ Время: %s\n\n",
                    $coin->currency->name,
                    $coin->volume_diff_percent,
                    $coin->price_change_percent,
                    $coin->created_at->format('H:i')
                );
            }

            $message .= "\n💡 _Период анализа: {$periodMinutes} минут_";

            $this->telegram->sendMessage($message);

        } catch (\Exception $e) {
            Log::error('Error analyzing coins: ' . $e->getMessage());
            $this->error('Failed to analyze coins: ' . $e->getMessage());
        }
    }
}
