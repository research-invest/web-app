<?php

/**
 * php artisan alert-usdt:eth-printer:run
 */

namespace App\Console\Commands\Alerts;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UsdtEthPrinterNotify extends Command
{
    protected $signature = 'alert-usdt:eth-printer:run
        {--force : Send regardless of state hash (debug)}
        {--limit=10000 : Max rows to request from TronScan (safety cap)}';

    protected $description = 'Fetch ETH USDT printer events from TronScan, build Pine arrays, and notify Telegram if changed';

    // storage/app/public/<this> (используем публичный диск для лучшей совместимости)
    protected string $statePath = 'usdt_eth_state.json';

    private TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        parent::__construct();
        $this->telegram = $telegram;
        $this->telegram->setNotificationBot();
    }

    public function handle(): int
    {
        try {
            $url = 'https://apilist.tronscanapi.com/api/usdt/turnover';
            $limit = (int)$this->option('limit'); // 10 по умолчанию
            $limit = $limit > 0 ? $limit : 10;

            // 1) Fetch
            $resp = Http::timeout(30)->get($url, [
                'source' => 'eth',
                'sort' => 'desc',
                'limit' => $limit,
                'start' => 0,
            ]);

            if (!$resp->ok()) {
                $this->error('HTTP error from TronScan: ' . $resp->status());
                return self::FAILURE;
            }

            $json = $resp->json();
            $data = $json['data'] ?? $json['list'] ?? $json ?? [];

            if (!is_array($data) || empty($data)) {
                $this->warn('No data returned from TronScan.');
                return self::SUCCESS;
            }

            // 2) Prepare events: keep only days with change (issue - redeem != 0), normalize to UTC midnight
            $events = [];
            foreach ($data as $row) {
                $issue = isset($row['issue']) ? (float)$row['issue'] : 0.0;
                $redeem = isset($row['redeem']) ? (float)$row['redeem'] : 0.0;
                $timeMs = isset($row['time']) ? (int)$row['time'] : null;
                if ($timeMs === null) continue;

                $net = $issue - $redeem;
                if (abs($net) < 1e-9) continue;

                $midnightMs = intdiv($timeMs, 86400000) * 86400000;
                $events[] = ['ts' => $midnightMs, 'net_delta' => $net];
            }

            if (empty($events)) {
                $this->info('No non-zero events found.');
                return self::SUCCESS;
            }

            // 3) Asc order + dedup by ts
            usort($events, fn($a, $b) => $a['ts'] <=> $b['ts']);
            $uniq = [];
            $out = [];
            foreach ($events as $e) {
                if (isset($uniq[$e['ts']])) continue;
                $uniq[$e['ts']] = true;
                $out[] = $e;
            }
            $events = $out;

            $events = array_slice($events, -10);

            // 4) Build Pine arrays (без /* ... */, чтобы проще копипастить, но можешь вернуть комментарии если хочешь)
            $tsArr = implode(',', array_map(fn($e) => (string)$e['ts'], $events));
            $valArr = implode(',', array_map(function ($e) {
                $v = (float)$e['net_delta'];
                return rtrim(rtrim(number_format($v, 6, '.', ''), '0'), '.');
            }, $events));

            $evTsLine = "var int[]   evTs    = array.from(" . $tsArr . ")";
            $evDeltaLine = "var float[] evDelta = array.from(" . $valArr . ")";

            // 5) State compare: hash + (last_ts,last_val)
            $hashNow = sha1($evTsLine . '|' . $evDeltaLine);
            $latest = end($events);
            $stateNew = [
                'last_ts' => $latest['ts'] ?? null,
                'last_val' => (string)($latest['net_delta'] ?? ''),
                'hash' => $hashNow,
                'count' => count($events),
                'updated' => now()->toIso8601String(),
            ];

            $prev = $this->loadState();
            $force = (bool)$this->option('force');
            $needSend = $force;

            if (!$needSend) {
                if (!$prev) {
                    $this->line('State missing -> first send.');
                    $needSend = true;
                } else {
                    // Проверяем изменения: новый hash означает новые данные
                    $hashChanged = ($prev['hash'] ?? '') !== $stateNew['hash'];

                    // Дополнительная проверка на новые события (более поздняя временная метка)
                    $newerData = ($prev['last_ts'] ?? 0) < $stateNew['last_ts'];

                    $needSend = $hashChanged || $newerData;

                    $this->line('State compare: ' . ($needSend ? 'CHANGED' : 'no changes') .
                        " (prev_hash=" . substr($prev['hash'] ?? 'none', 0, 8) . "..., new_hash=" . substr($stateNew['hash'], 0, 8) . "...)" .
                        " (prev_count=" . ($prev['count'] ?? 'n/a') . ", new_count=" . $stateNew['count'] . ")" .
                        " (prev_ts=" . ($prev['last_ts'] ?? 'none') . ", new_ts=" . $stateNew['last_ts'] . ")");
                }
            }

            if (!$needSend) {
                $this->info('No changes since last notification.');
                // всё равно обновим state на случай ручной чистки/сбоя
                $this->saveState($stateNew);
                return self::SUCCESS;
            }

            // 6) Send to Telegram
            $text = "USDT ETH printer arrays for Pine (paste into script):\n"
                . "```\n{$evTsLine}\n{$evDeltaLine}\n```";


            $this->telegram->sendMessage($text, null, 'Markdown');

            // 7) Save state (явно укажем диск и путь)
            $this->saveState($stateNew);

            $this->info('Sent to Telegram and updated local state: ' . $this->stateAbsolutePath());
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /** === STATE HELPERS === */

    protected function loadState(): ?array
    {
        $disk = Storage::disk('public');
        if (!$disk->exists($this->statePath)) {
            $this->line('State file not found: ' . $this->stateAbsolutePath());
            return null;
        }
        $raw = $disk->get($this->statePath);
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            $this->warn('State file is corrupted. Rewriting...');
            return null;
        }
        return $json;
    }

    protected function saveState(array $state): void
    {
        $disk = Storage::disk('public');
        $ok = $disk->put($this->statePath, json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if (!$ok) {
            $this->warn('Failed to write state to: ' . $this->stateAbsolutePath());
        } else {
            $this->line('State saved: ' . $this->stateAbsolutePath());
        }
    }

    protected function stateAbsolutePath(): string
    {
        return Storage::disk('public')->path($this->statePath);
    }
}
