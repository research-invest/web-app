<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $token;
    private ?string $notify_token;
    private ?string $chatId;
    private string $apiUrl = 'https://api.telegram.org/bot';

    public function __construct(private $isNotificationBot = false)
    {
        $this->token = config('services.telegram.bot_token');
        $this->notify_token = config('services.telegram.notify_bot_token');
        $this->chatId = config('services.telegram.chat_id');
    }

    public function sendMessage(string $message, $chatId = null, string $parseMode = 'HTML'): bool
    {
        try {
            $response = Http::post($this->apiUrl . $this->getToken() . '/sendMessage', [
                'chat_id' => $chatId ?: $this->chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram notification error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPhoto(string $caption, $image, $chatId = null): bool
    {
        // Если $image это URL
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $response = Http::post($this->apiUrl . $this->getToken() . '/sendPhoto', [
                'chat_id' => $chatId ?: $this->chatId,
                'caption' => $caption,
                'photo' => $image,
                'parse_mode' => 'HTML'
            ]);
        } // Если $image это локальный файл или бинарные данные
        else {
            $response = Http::attach(
                'photo', $image, 'chart.png'
            )->post($this->apiUrl . $this->getToken() . '/sendPhoto', [
                'chat_id' => $chatId ?: $this->chatId,
                'caption' => $caption,
                'parse_mode' => 'HTML'
            ]);
        }
        $result = $response->json();
        if (!$result['ok']) {
            Log::error('Telegram sendPhoto error', [
                'result' => $result,
            ]);
        }
        return $result['ok'] ?? false;
    }

    public function setNotificationBot(): void
    {
        $this->isNotificationBot = true;
    }

    protected function getToken(): string
    {
        if ($this->isNotificationBot) {
            return $this->notify_token;
        }

        return $this->token;
    }
}
