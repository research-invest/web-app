<?php

namespace App\Orchid\Filters\Deals\Funding;

use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class FiltersDealsLayout extends Selection
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
            DealStatusFilter::class,
        ];
    }
}
