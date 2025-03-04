<?php


namespace App\Orchid\Layouts\Trading\Deals\Funding;

use App\Helpers\MathHelper;
use App\Models\Funding\FundingDealConfig;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ConfigListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'configs';


    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->width(20)
                ->render(fn(FundingDealConfig $funding) => Link::make((string)$funding->id)
                    ->rawClick()
                    ->route('platform.trading.funding_deals', $funding)
                ),

            TD::make('name', 'Name')
                ->render(fn(FundingDealConfig $funding) => Link::make($funding->name)
                    ->rawClick()
                    ->route('platform.trading.funding_deals', $funding)
                ),
            TD::make('exchange', 'exchange')
                ->defaultHidden(),

            TD::make('min_funding_rate', 'min funding rate'),
            TD::make('position_size', 'position size'),
            TD::make('leverage', 'leverage'),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(FundingDealConfig $funding) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->confirm('Вы уверены?')
                            ->method('remove', [
                                'id' => $funding->id,
                            ]),
                    ])),
        ];
    }
}
