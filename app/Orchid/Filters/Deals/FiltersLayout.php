<?php

namespace App\Orchid\Filters\Deals;

use App\Orchid\Filters\Currency\CurrenciesIdFilter;
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
            PeriodFilter::class,
            StatusFilter::class,
            CurrenciesIdFilter::class,
        ];
    }
}
