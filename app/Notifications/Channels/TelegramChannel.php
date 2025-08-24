<?php

namespace App\Notifications\Channels;

use App\Services\TelegramService;
use Illuminate\Notifications\Notification;

readonly class TelegramChannel
{
    public function __construct(
        private TelegramService $telegram
    ) {}

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        $this->telegram->setNotificationBot();

        $message = $notification->toTelegram($notifiable);

        // Определяем chat_id: если у пользователя есть telegram_chat_id, используем его
        $chatId = null;
        if (isset($notifiable->telegram_chat_id) && !empty($notifiable->telegram_chat_id)) {
            $chatId = $notifiable->telegram_chat_id;
        }

        if (is_string($message)) {
            $this->telegram->sendMessage($message, $chatId);
        } elseif (is_array($message)) {
            $this->telegram->sendMessage(
                $message['text'] ?? '',
                $message['chat_id'] ?? $chatId,
                $message['parse_mode'] ?? 'HTML'
            );
        }
    }
}
