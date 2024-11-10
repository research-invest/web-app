<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Trading\Deals;

use App\Models\Trade;
use App\Orchid\Layouts\Trading\Deals\ListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class DealsListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'trades' => Trade::filters()
                ->paginate(30),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Сделки';
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
            Link::make('Add')
                ->icon('plus')
                ->rawClick()
                ->route('platform.trading.deal.create')
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
            ListLayout::class,
        ];
    }

}
