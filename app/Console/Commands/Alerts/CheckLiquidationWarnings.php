<?php
/**
 * php artisan trades:check-liquidation
 */

namespace App\Console\Commands\Alerts;

use App\Models\Trade;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckLiquidationWarnings extends Command
{
    protected $signature = 'trades:check-liquidation';
    protected $description = '';

    // Уровни предупреждений в процентах до ликвидации
    private const WARNING_LEVELS = [
        'CRITICAL' => 5,  // Красный уровень - меньше 5%
        'WARNING' => 10,  // Желтый уровень - меньше 10%
        'NOTICE' => 15    // Синий уровень - меньше 15%
    ];

    // Интервал между повторными уведомлениями (в минутах)
    private const int NOTIFICATION_COOLDOWN = 30;

    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    public function handle()
    {
        $openTrades = Trade::where('status', 'open')->get();

        foreach ($openTrades as $trade) {
            $this->checkTradePosition($trade);
        }
    }

    private function checkTradePosition(Trade $trade)
    {
        $currentPrice = $trade->currency->last_price ?? $trade->entry_price;
        $distanceToLiquidation = $trade->getDistanceToLiquidation($currentPrice);

        if ($distanceToLiquidation === null) {
            return;
        }

        // Определяем уровень опасности
        $warningLevel = $this->getWarningLevel($distanceToLiquidation);

        if ($warningLevel) {
            $this->sendWarningIfNeeded($trade, $warningLevel, $distanceToLiquidation);
        }
    }

    private function getWarningLevel(float $distance): ?string
    {
        foreach (self::WARNING_LEVELS as $level => $threshold) {
            if ($distance <= $threshold) {
                return $level;
            }
        }
        return null;
    }

    private function sendWarningIfNeeded(Trade $trade, string $level, float $distance)
    {
        $cacheKey = "liquidation_warning_{$trade->id}_{$level}";

        if (!Cache::has($cacheKey)) {
            $message = $this->formatWarningMessage($trade, $level, $distance);

            if ($this->telegram->sendMessage($message)) {
                Cache::put($cacheKey, true, now()->addMinutes(self::NOTIFICATION_COOLDOWN));
            }
        }
    }

    private function formatWarningMessage(Trade $trade, string $level, float $distance): string
    {
        $emoji = match($level) {
            'CRITICAL' => '🚨',
            'WARNING' => '⚠️',
            'NOTICE' => 'ℹ️',
            default => '❗'
        };

        $currentPrice = $trade->currency->last_price ?? $trade->entry_price;
        $liquidationPrice = $trade->getLiquidationPrice();

        $message = "{$emoji} <b>{$level}: Риск ликвидации!</b>\n\n";
        $message .= "🔸 Пара: <b>{$trade->currency->name}</b>\n";
        $message .= "🔸 Тип: " . ($trade->position_type === 'long' ? 'LONG' : 'SHORT') . "\n";
        $message .= "🔸 Плечо: {$trade->leverage}x\n\n";

        $message .= "📊 <b>Текущая ситуация:</b>\n";
        $message .= "• Средняя цена: " . number_format($trade->getAverageEntryPrice(), 8) . "\n";
        $message .= "• Текущая цена: " . number_format($currentPrice, 8) . "\n";
        $message .= "• Цена ликвидации: " . number_format($liquidationPrice, 8) . "\n";
        $message .= "• До ликвидации: " . number_format($distance, 2) . "%\n\n";

        $unrealizedPnl = $trade->getUnrealizedPnL($currentPrice);
        $roe = $trade->getCurrentRoe($currentPrice);

        $message .= "💰 <b>P&L:</b>\n";
        $message .= "• PNL: " . number_format($unrealizedPnl, 2) . " USDT\n";
        $message .= "• ROE: " . number_format($roe, 2) . "%\n\n";

        $message .= "⚡ <b>Рекомендуемые действия:</b>\n";
        $message .= match($level) {
            'CRITICAL' => "• Срочно добавьте маржу\n• Частично закройте позицию\n• Измените стоп-лосс",
            'WARNING' => "• Рассмотрите добавление маржи\n• Подготовьтесь к возможному закрытию",
            'NOTICE' => "• Отслеживайте ситуацию\n• Проверьте риск-менеджмент",
            default => "• Примите необходимые меры"
        };

        return $message;
    }
}
