<?php
/**
 * php artisan alert-free-space:run
 */

namespace App\Console\Commands\Alerts;

use App\Helpers\ArrayHelper;
use App\Helpers\Development;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class FreeSpaceAlert extends Command
{
    protected $signature = 'alert-free-space:run';
    protected $description = 'Проверяет свободное место на диске и отправляет уведомление, если его недостаточно';

    private int $minimumFreeSpace = 5; // Минимальное свободное место в ГБ


    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    public function handle()
    {
        $timeStart = microtime(true);

        $this->process();

        $this->info('Использовано памяти: ' . Development::getPeakMemoryUsageInMb());
        $this->info('Время выполнения в секундах: ' . Development::getFormatDiffMicroTime($timeStart));
    }

    private function process()
    {
        $freeSpace = $this->getFreeSpace();

        if ($freeSpace < $this->minimumFreeSpace) {
            $message = [
                '⚠️ Внимание! Недостаточно свободного места на диске' => "\n",
                'Текущее свободное место: ' => round($freeSpace, 2) . ' ГБ',
                'Минимальное требуемое место: ' => $this->minimumFreeSpace . ' ГБ',
            ];

            $this->telegram->sendMessage(ArrayHelper::implodeWithKeys($message, ' '));

            $this->error('Недостаточно свободного места на диске!');
        } else {
            $this->info('Достаточно свободного места на диске: ' . round($freeSpace, 2) . ' ГБ');
        }
    }

    private function getFreeSpace(): float|int
    {
        $freeSpace = disk_free_space('/');
        return $freeSpace / Development::byteToGb();
    }
}
