<?php
/**
 * php artisan pairs:check-favorites
 */


namespace App\Console\Commands\Alerts;

use App\Models\Currency;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use App\Models\User;

class CheckFavoritePairs extends Command
{
    protected $signature = 'pairs:check-favorites';
    protected $description = 'Анализ избранных торговых пар';

    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }
    public function handle()
    {
        $users = User::with('favorites.currency')->get();

        foreach ($users as $user) {
            if (!$user->telegram_chat_id || $user->favorites->isEmpty()) {
                continue;
            }

            $message = " *Ваши избранные пары*\n\n";

            /**
             * @var Currency $currency
             */
            foreach ($user->favorites as $favorite) {
                $currency = $favorite->currency;
                $change = $currency->price_change_24h;
                $trend = $change >= 0 ? '🟢' : '🔴';
                $arrow = $change >= 0 ? '↗️' : '↘️';

                $message .= sprintf(
                    "%s *%s* %s\n".
                    "Цена: `%.4f`\n".
                    "Изменение (24ч): `%.2f%%`\n\n",
                    $trend,
                    $currency->code,
                    $arrow,
                    $currency->last_price,
                    $change
                );
            }

            $this->telegram->sendMessage($message, '-1002321524146');
        }

        $this->info('Отчеты по избранным парам отправлены пользователям');
    }
}
