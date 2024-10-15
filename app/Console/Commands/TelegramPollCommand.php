<?php

/**
 * php artisan telegram:poll
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Http\Controllers\TelegramController;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = '';

    public function handle()
    {
        $this->info('Запуск слушателя telegram...');

        $controller = new TelegramController();
        $offset = 0;

        while (true) {
            $updates = Telegram::getUpdates(['offset' => $offset, 'limit' => 100]);

            foreach ($updates as $update) {
                $controller->handleUpdate($update);
                $offset = $update->getUpdateId() + 1;
            }

            sleep(1);
        }
    }
}
