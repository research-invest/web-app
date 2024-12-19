<?php
/**
 * php artisan trades:notify-pnl
 */

namespace App\Console\Commands\Alerts;

use App\Models\Trade;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendTradePnLNotification extends Command
{
    protected $signature = 'trades:notify-pnl';
    protected $description = '';

    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    public function handle()
    {
        $openTrades = Trade::where('status', 'open')->get();

        if ($openTrades->isEmpty()) {
            return;
        }

        $message = $this->formatMessage($openTrades);

        if ($this->telegram->sendMessage($message)) {
            $this->info('PNL notification sent successfully');
        } else {
            $this->error('Failed to send PNL notification');
        }
    }

    private function formatMessage($trades): string
    {
        $message = "🔄 <b>Состояние открытых позиций</b>\n\n";

        $totalPnl = 0;

        /**
         * @var Trade $trade
         */
        foreach ($trades as $trade) {
            $currentPrice = $trade->currency->last_price ?? $trade->entry_price;
            $unrealizedPnl = $trade->getUnrealizedPnL($currentPrice);
            $roe = $trade->getCurrentRoe($currentPrice);
            $liquidationPrice = $trade->getLiquidationPrice();
            $distanceToLiquidation = $trade->getDistanceToLiquidation($currentPrice);
            $totalPnl += $unrealizedPnl;

            $emoji = $unrealizedPnl >= 0 ? '📈' : '📉';
            $direction = $trade->position_type === 'long' ? 'LONG' : 'SHORT';

            $message .= "{$emoji} <b>{$trade->currency->name}</b> {$direction}\n";
            $message .= "💰 PNL: " . number_format($unrealizedPnl, 2) . " USDT\n";
            $message .= "📊 ROE: " . number_format($roe, 2) . "%\n";
            $message .= "💵 Размер позиции: " . number_format($trade->getCurrentPositionSize(), 8) . "\n";
            $message .= "💵 Средняя цена: " . number_format($trade->getAverageEntryPrice(), 8) . "\n";
            $message .= "🎯 Текущая цена: " . number_format($currentPrice, 8) . "\n";
            $message .= "⚠️ Ликвидация: " . number_format($liquidationPrice, 8) . "\n";
            $message .= "🛡️ До ликвидации: " . number_format($distanceToLiquidation, 2) . "%\n\n";
        }

        $message .= "📊 <b>Общий PNL: " . number_format($totalPnl, 2) . " USDT</b>";

        return $message;
    }
}
