<?php
/**
 * php artisan wallets:report
 */

namespace App\Console\Commands\BtcWallets;

use App\Helpers\Development;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateWalletReport extends Command
{
    protected $signature = 'wallets:report';
    protected $description = 'Генерация телеграм-отчета по китовым кошелькам';

    // Минимальный процент изменения для учета в отчете
    private const float MIN_CHANGE_PERCENT = 5;

    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
    }

    public function handle()
    {
        $wallets = DB::table('wallets')
            ->select(['address', 'balance', 'diff_percent'])
            ->where('diff_percent', '>', self::MIN_CHANGE_PERCENT)
            ->orWhere('diff_percent', '<', -self::MIN_CHANGE_PERCENT)
            ->orderBy(DB::raw('ABS(diff_percent)'), 'desc')
            ->get();

        if ($wallets->isEmpty()) {
            $this->info('Нет изменений для отчета');
            return;
        }

        $totalBalance = DB::table('wallets')->sum('balance');

        $grown = $wallets->filter(fn($w) => $w->diff_percent > 0)->count();
        $dropped = $wallets->filter(fn($w) => $w->diff_percent < 0)->count();
        $unchanged = DB::table('wallets')
            ->where('diff_percent', '>=', -self::MIN_CHANGE_PERCENT)
            ->where('diff_percent', '<=', self::MIN_CHANGE_PERCENT)
            ->count();

        // Топ gainers и losers
        $topGainers = $wallets->filter(fn($w) => $w->diff_percent > 0)->take(3);
        $topLosers = $wallets->filter(fn($w) => $w->diff_percent < 0)->take(3);

        $now = Carbon::now()->format('d.m.Y H:i');

        $message = "📊 *Китовый отчёт* за *{$now}*\n";
        $message .= "Общий баланс: ₿ *" . number_format($totalBalance, 2, '.', ' ') . "*\n\n";

        $message .= "📈 Рост: *{$grown}* кошельков\n";
        $message .= "📉 Падение: *{$dropped}* кошельков\n";
        $message .= "➖ Без изменений: *{$unchanged}*\n\n";

        $message .= "🟢 *Топ-3 роста:*\n";
        foreach ($topGainers as $w) {
            $message .= "• +" . number_format($w->diff_percent, 2) . "% — `" . $w->address . "...`\n";
        }

        $message .= "\n🔴 *Топ-3 падений:*\n";
        foreach ($topLosers as $w) {
            $message .= "• " . number_format($w->diff_percent, 2) . "% — `" . $w->address . "...`\n";
        }

        $this->sendToTelegram($message);
        $this->info('Отчёт отправлен в Telegram!');
    }

    protected function sendToTelegram(string $text): void
    {
        if (Development::isLocal()) {
            $this->telegram->sendMessage($text, '-1002466965376', 'Markdown'); //local
            return;
        }

        $this->telegram->sendMessage($text, '-1002321524146', 'Markdown'); // prod
    }
}

