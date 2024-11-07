<?php

namespace App\Orchid\Filters\Statistics\VolumeByRange;

use App\Orchid\Filters\Statistics\SingleCurrencyFilter;
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
            SingleCurrencyFilter::class,
            Interval::class,
        ];
    }
}
