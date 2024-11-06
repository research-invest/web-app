<?php

namespace App\Orchid\Filters\Statistics\TopPerformingCoins;

use App\Orchid\Filters\Statistics\Normalize\CurrencyFilter;
use App\Orchid\Filters\Statistics\Normalize\IntervalsFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class FiltersLayout extends Selection
{

    /**
     * @var string
     */
    public $template = self::TEMPLATE_LINE;
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            PriceChangePercent::class,
            VolumeDiffPercent::class,
        ];
    }
}
