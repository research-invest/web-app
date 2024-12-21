<?php
/**
 * php artisan trades:notify-pnl
 */

namespace App\Console\Commands\Alerts;

use App\Helpers\MathHelper;
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
            $direction = $trade->isTypeLong() ? 'LONG' : 'SHORT';

            $message .= "{$emoji} <b>{$trade->currency->name}</b> {$direction}\n";
            $message .= "💰 PNL: " . MathHelper::formatNumber($unrealizedPnl) . " USDT\n";
            $message .= "📊 ROE: " . MathHelper::formatNumber($roe) . "%\n";
            $message .= "💵 Размер позиции: " . MathHelper::formatNumber($trade->getCurrentPositionSize()) . "\n";
            $message .= "💵 Средняя цена: " . MathHelper::formatNumber($trade->getAverageEntryPrice()) . "\n";
            $message .= "🎯 Текущая цена: " . MathHelper::formatNumber($currentPrice) . "\n";
            $message .= "⚠️ Ликвидация: " . MathHelper::formatNumber($liquidationPrice) . "\n";
            $message .= "🛡️ До ликвидации: " . MathHelper::formatNumber($distanceToLiquidation) . "%\n\n";
        }

        $message .= "📊 <b>Общий PNL: " . MathHelper::formatNumber($totalPnl) . " USDT</b>";

        return $message;
    }
}
