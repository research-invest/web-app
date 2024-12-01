<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $token;
    private ?string $chatId;
    private string $apiUrl = 'https://api.telegram.org/bot';

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
    }

    public function sendMessage(string $message, $chatId = null): bool
    {
        try {
            $response = Http::post($this->apiUrl . $this->token . '/sendMessage', [
                'chat_id' => $chatId ?: $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram notification error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPhoto(string $caption, $image, $chatId = null): void
    {
        // Если $image это URL
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $response = Http::post($this->apiUrl . $this->token . '/sendPhoto', [
                'chat_id' => $chatId ?: $this->chatId,
                'caption' => $caption,
                'photo' => $image,
                'parse_mode' => 'HTML'
            ]);
        }
        // Если $image это локальный файл или бинарные данные
        else {
            $response = Http::attach(
                'photo', $image, 'chart.png'
            )->post($this->apiUrl . $this->token . '/sendPhoto', [
                'chat_id' => $chatId ?: $this->chatId,
                'caption' => $caption,
                'parse_mode' => 'HTML'
            ]);
        }
    }
}
