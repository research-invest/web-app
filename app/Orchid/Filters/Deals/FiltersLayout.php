<?php

namespace App\Orchid\Filters\Deals;

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
        ];
    }
}
