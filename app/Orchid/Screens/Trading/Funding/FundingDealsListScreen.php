<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Trading\Funding;

use App\Helpers\UserHelper;
use App\Models\Currency;
use App\Models\Funding\FundingDealConfig;
use App\Models\Funding\FundingSimulation;
use App\Models\Trade;
use App\Models\TradeOrder;
use App\Orchid\Layouts\Trading\Deals\Funding\ConfigListLayout;
use App\Orchid\Layouts\Trading\Deals\Funding\DealsListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class FundingDealsListScreen extends Screen
{

    public $config;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(FundingDealConfig $config): iterable
    {
        $config->load('deals');

        return [
            'configs' => FundingDealConfig::filters()
                ->byCreator()
                ->latest()
                ->paginate(25),
            'config' => $config,
            'deals' => $config->deals,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Конфигуратор funding сделок';
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
            ModalToggle::make('Добавить сделку')
                ->modal('addConfigModal')
                ->method('create')
                ->icon('plus')
                ->class('btn btn-primary'),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return string[]|\Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        $layout = [
            Layout::modal('addConfigModal', [
                Layout::rows([

                    Input::make('config.name')
                        ->title('Название'),

                    Select::make('config.exchange')
                        ->options(Currency::getExchanges())
                        ->title('Выберите биржу'),

                    Input::make('config.min_funding_rate')
                        ->title('min_funding_rate')
                        ->type('number')
                        ->step('0.00000001')
                        ->required(),

                    Input::make('config.position_size')
                        ->title('position_size')
                        ->value(200)
                        ->type('number')
                        ->required(),

                    Input::make('config.leverage')
                        ->title('leverage')
                        ->value(10)
                        ->type('number')
                        ->required(),

                    TextArea::make('config.notes')
                        ->title('Комментарий')
                        ->rows(3),
                ])
            ])->title('Добавить сделку'),
        ];

        if ($this->config->exists) {
            $layout[] = Layout::split([
                ConfigListLayout::class,
                DealsListLayout::class,
            ])->ratio('40/60');
        } else {
            $layout[] = ConfigListLayout::class;
        }

        return $layout;
    }

    public function create(Request $request)
    {
        $data = $request->collect('config')->toArray();

        $config = new FundingDealConfig();

        $config->fill($data)
            ->fill([
                'user_id' => UserHelper::getId()
            ]);

        $config->save();

        Toast::success('Добавлено');
    }


    public function remove(Request $request)
    {
        FundingDealConfig::findOrFail($request->get('id'))->delete();

        Toast::success('Сделка удалена');

        return redirect()->route('platform.trading.funding_deals');
    }

}
