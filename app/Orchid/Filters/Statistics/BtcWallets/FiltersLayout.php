<?php

namespace App\Orchid\Filters\Statistics\BtcWallets;

use App\Orchid\Filters\Statistics\SingleCurrencyFilter;
use App\Orchid\Filters\Statistics\VolumeByRange\Interval;
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
            TypesFilter::class,
            AddressFilter::class,
        ];
    }
}
