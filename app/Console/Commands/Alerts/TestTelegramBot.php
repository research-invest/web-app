<?php
/**
 * php artisan test-telegram-bot:run
 */

namespace App\Console\Commands\Alerts;

use App\Helpers\Development;
use App\Helpers\TelegramHelper;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class TestTelegramBot extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-telegram-bot:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';


    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeStart = microtime(true);

        $this->telegram->sendMessage('Привет, это тестовое сообщение от бота ❤️');

        $this->info('Использовано памяти: ' . Development::getPeakMemoryUsageInMb());
        $this->info('Время выполнения в секундах: ' . Development::getFormatDiffMicroTime($timeStart));
    }

}
