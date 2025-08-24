<?php

namespace App\Notifications;

use App\Models\TradingViewWebhook;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TradingViewWebhookReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TradingViewWebhook $webhook
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): string
    {
        $actionEmoji = match($this->webhook->action) {
            'buy' => '🟢',
            'sell' => '🔴',
            'close' => '🟡',
            default => '📢'
        };

        $message = "{$actionEmoji} <b>TradingView Сигнал</b>\n\n";
        $message .= "📈 <b>Символ:</b> {$this->webhook->symbol}\n";
        $message .= "⚡ <b>Действие:</b> " . strtoupper($this->webhook->action) . "\n";

        if ($this->webhook->strategy) {
            $message .= "🎯 <b>Стратегия:</b> {$this->webhook->strategy}\n";
        }

        if ($this->webhook->price) {
            $message .= "💰 <b>Цена:</b> " . number_format($this->webhook->price, 8) . "\n";
        }

        if ($this->webhook->exchange) {
            $message .= "🏢 <b>Биржа:</b> " . strtoupper($this->webhook->exchange) . "\n";
        }

        if ($this->webhook->timeframe) {
            $message .= "⏰ <b>Таймфрейм:</b> {$this->webhook->timeframe}\n";
        }

        $message .= "\n📅 <b>Время:</b> " . $this->webhook->created_at->format('d.m.Y H:i:s');

        if (isset($this->webhook->raw_data['volume'])) {
            $message .= "\n📊 <b>Объем:</b> " . number_format((float)$this->webhook->raw_data['volume'], 2);
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'webhook_id' => $this->webhook->id,
            'symbol' => $this->webhook->symbol,
            'action' => $this->webhook->action,
            'strategy' => $this->webhook->strategy,
            'price' => $this->webhook->price,
            'exchange' => $this->webhook->exchange,
            'timeframe' => $this->webhook->timeframe,
            'created_at' => $this->webhook->created_at->toISOString(),
        ];
    }
}
