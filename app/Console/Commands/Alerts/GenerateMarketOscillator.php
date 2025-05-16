<?php
/**
 * php artisan market:oscillator
 */
namespace App\Console\Commands\Alerts;

use App\Models\Trade;
use App\Services\ChartGenerator;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMarketOscillator extends Command
{
    protected $signature = 'market:oscillator';
    protected $description = 'Генерирует осциллятор рынка на основе фиксированных лонг и шорт сделок';

    private TelegramService $telegram;
    private ChartGenerator $chartGenerator;
    private const LONG_TRADE_ID = 'LONG_TRADE_ID';
    private const SHORT_TRADE_ID = 'SHORT_TRADE_ID';

    public function __construct(TelegramService $telegram, ChartGenerator $chartGenerator)
    {
        parent::__construct();
        $this->telegram = $telegram;
        $this->chartGenerator = $chartGenerator;
    }

    public function handle()
    {
        /**
         * @var $longTrade Trade
         * @var $shortTrade Trade
         */
        $longTrade = Trade::find(env(self::LONG_TRADE_ID));
        $shortTrade = Trade::find(env(self::SHORT_TRADE_ID));

        if (!$longTrade || !$shortTrade) {
            $this->error('Не найдены фиксированные сделки. Проверьте настройки в .env');
            return;
        }

        // Получаем историю PNL для обеих сделок
        $longHistory = $longTrade->pnlHistory()->orderBy('created_at')->limit(20)->get();
        $shortHistory = $shortTrade->pnlHistory()->orderBy('created_at')->limit(20)->get();

        if ($longHistory->isEmpty() || $shortHistory->isEmpty()) {
            $this->error('Нет истории PNL для одной или обеих сделок');
            return;
        }

        // Формируем данные для графика
        $chartData = [];
        $timestamps = [];

        // Собираем все уникальные временные метки
        foreach ($longHistory as $record) {
            $timestamps[$record->created_at->timestamp] = $record->created_at;
        }
        foreach ($shortHistory as $record) {
            $timestamps[$record->created_at->timestamp] = $record->created_at;
        }
        ksort($timestamps);

        // Для каждой временной метки рассчитываем осциллятор
        foreach ($timestamps as $timestamp => $date) {
            $longPnl = $longHistory->where('created_at', $date)->first()?->unrealized_pnl ?? 0;
            $shortPnl = $shortHistory->where('created_at', $date)->first()?->unrealized_pnl ?? 0;

            // Нормализуем PNL к диапазону -100 до 100
            $maxPnl = max(abs($longPnl), abs($shortPnl));
            if ($maxPnl == 0) {
                $oscillator = 0;
            } else {
                $longNormalized = ($longPnl / $maxPnl) * 100;
                $shortNormalized = ($shortPnl / $maxPnl) * 100;
                $oscillator = $longNormalized - $shortNormalized;
            }

            $chartData[] = [
                'timestamp' => $date->format('Y-m-d H:i:s'),
                'score' => round($oscillator, 2)
            ];
        }

        // Генерируем график
        $chartImage = $this->chartGenerator->generateIndexChart($chartData);

        // Формируем сообщение
        $currentOscillator = end($chartData)['score'];
        $message = "📊 <b>Осциллятор рынка: {$currentOscillator}</b>\n\n";

        if ($currentOscillator > 0) {
            $message .= "🟢 Преобладает лонг позиция";
        } elseif ($currentOscillator < 0) {
            $message .= "🔴 Преобладает шорт позиция";
        } else {
            $message .= "⚪ Нейтральное состояние";
        }

        $this->telegram->sendPhoto($chartImage, $message, '-1002321524146');
        $this->info('Осциллятор успешно отправлен');
    }
}
