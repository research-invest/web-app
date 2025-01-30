<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Statistics\Funding;

use App\Models\Currency;
use App\Models\User;
use App\Orchid\Filters\Statistics\Funding\FiltersLayout;
use App\Orchid\Layouts\Currency\Funding\CurrenciesFundingListLayout;
use Orchid\Screen\Screen;

class FundingRatesListScreen extends Screen
{

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'currencies' => Currency::filters(FiltersLayout::class)
                ->with(['latestFundingRate'])
                ->isActive()
                ->features()
                ->select('currencies.*')
                ->paginate(30),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Funding Rates';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return '';
    }

    public function permission(): ?iterable
    {
        return [
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return string[]|\Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            FiltersLayout::class,
            CurrenciesFundingListLayout::class,
        ];
    }
}
