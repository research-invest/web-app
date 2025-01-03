<?php

namespace App\Orchid\Screens\Statistics\TopPerformingCoins;

use App\Models\Currency;
use App\Orchid\Filters\Currency\FiltersLayout;
use App\Orchid\Layouts\Statistics\TopPerformingCoins\CurrenciesListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Action;
use Orchid\Screen\Screen;

class TopPerformingCoinsTable extends Screen
{

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Request $request): iterable
    {
        return [
            'currencies' => Currency::filters(FiltersLayout::class)
                ->isActive()
                ->filters()
                ->select('currencies.*',
                    'latest_snapshot.price_change_percent as snapshot_price_change_percent',
                    'latest_snapshot.volume_diff_percent as snapshot_volume_diff_percent',
                    'latest_snapshot.price as snapshot_price',
                    'latest_snapshot.created_at as snapshot_created_at',
                )
                ->joinSub(
                    function ($query) {
                        $query->select('currency_id', 'price_change_percent','volume_diff_percent','price', 'created_at')
                            ->from('top_performing_coin_snapshots as tps1')
                            ->whereNotExists(function ($query) {
                                $query->from('top_performing_coin_snapshots as tps2')
                                    ->whereColumn('tps1.currency_id', 'tps2.currency_id')
                                    ->whereColumn('tps2.created_at', '>', 'tps1.created_at');
                            });
                    },
                    'latest_snapshot', 'currencies.id', '=', 'latest_snapshot.currency_id'
                )
                ->orderByDesc('latest_snapshot.created_at')
                ->distinct()
                ->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'История хорошей динамики';
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
     * @return Action[]
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
            CurrenciesListLayout::class,
        ];
    }
}
