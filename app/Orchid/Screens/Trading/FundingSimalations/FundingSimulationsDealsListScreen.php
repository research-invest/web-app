<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Trading\FundingSimalations;

use App\Models\Funding\FundingSimulation;
use App\Orchid\Layouts\Trading\Deals\FundingSimulations\ListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class FundingSimulationsDealsListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'trades' => FundingSimulation::filters()
                ->with('currency')
                ->latest()
                ->paginate(25),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Эмуляция funding сделок';
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
            ListLayout::class,
        ];
    }

    public function remove(Request $request)
    {
        FundingSimulation::findOrFail($request->get('id'))->delete();

        Toast::success('Сделка удалена');

        return redirect()->route('platform.trading.funding_simulations');
    }

}
