<?php

namespace App\Orchid\Layouts\Trading\Deals\Funding;

use App\Helpers\MathHelper;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Carbon\Carbon;

class PriceHistoryLayout extends Table
{
    protected $target = 'price_history';

    protected function columns(): iterable
    {
        return [
            TD::make('timestamp', 'Время')
                ->render(function ($row) {
                    return Carbon::createFromTimestamp($row['timestamp'])->format('Y-m-d H:i:s');
                }),
            TD::make('price', 'Цена')
                ->render(fn($row) => number_format((float)$row['price'], 8)),
            TD::make('high', 'Максимум')
                ->render(fn($row) => number_format((float)($row['high'] ?? 0), 8)),
            TD::make('low', 'Минимум')
                ->render(fn($row) => number_format((float)($row['low'] ?? 0), 8)),
            TD::make('low', 'Diff')
                ->render(fn($row) => MathHelper::getPercentOfNumber(($row['low'] ?? 0), ($row['high'] ?? 0))),
            TD::make('execution_time', 'Время выполнения (мс)')
                ->render(fn($row) => number_format((float)($row['execution_time'] ?? 0), 2)),
        ];
    }
}
