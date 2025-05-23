<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Trading\Funding;

use App\Helpers\UserHelper;
use App\Models\Funding\FundingDealConfig;
use App\Orchid\Filters\Deals\Funding\FiltersDealsLayout;
use App\Orchid\Filters\Deals\Funding\FiltersLayout;
use App\Orchid\Layouts\Trading\Deals\Funding\ConfigListLayout;
use App\Orchid\Layouts\Trading\Deals\Funding\DealsListLayout;
use App\Orchid\Layouts\Trading\Deals\Funding\FormConfigLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
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
//        $config->load('deals');

        return [
            'config' => $config,
            'configs' => FundingDealConfig::filters()
                ->byCreator()
                ->latest()
                ->paginate(25),
            'deals' => $config->deals()
                ->filters(FiltersDealsLayout::class)
                ->defaultSort('created_at', 'desc')
                ->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {

        if ($this->config->exists) {
            return 'Выбранная сделка ' . $this->config->name;
        }
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
        $bar = [];

        if ($this->config->exists) {
            $bar[] = Link::make('Отчет')
                ->icon('bs.bar-chart')
                ->route('platform.trading.funding_deal.stats', $this->config);

            $bar[] = ModalToggle::make('Редактировать конфиг')
                ->modal('editConfigModal')
                ->method('save')
                ->icon('pencil')
                ->class('btn btn-primary');
        }

        if (!$this->config->exists) {
            $bar[] = ModalToggle::make('Добавить сделку')
                ->modal('addConfigModal')
                ->method('save')
                ->icon('plus')
                ->class('btn btn-primary');
        }

        return $bar;
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
                FormConfigLayout::class,
            ])->title('Добавить конфиг'),

            Layout::modal('editConfigModal', [
                FormConfigLayout::class,
            ])->title('Редактировать конфиг'),
        ];

        if ($this->config->exists) {
            $layout[] = Layout::split([
                ConfigListLayout::class,
                Layout::block([
                    FiltersDealsLayout::class,
                    DealsListLayout::class,
                ])->vertical(),
            ])->ratio('20/80');
        } else {
            $layout[] = ConfigListLayout::class;
        }

        return $layout;
    }

    public function save(Request $request)
    {
        $data = $request->collect('config')->toArray();

        $config = $this->config ?: new FundingDealConfig();

        $config->fill($data)
            ->fill([
                'user_id' => UserHelper::getId()
            ]);

        $config->save();

        Toast::success('Сохранено');
    }


    public function remove(Request $request)
    {
        FundingDealConfig::findOrFail($request->get('id'))->delete();

        Toast::success('Сделка удалена');

        return redirect()->route('platform.trading.funding_deals');
    }

}
