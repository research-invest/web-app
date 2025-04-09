<?php


namespace App\Orchid\Layouts\Trading\Deals\Funding;

use App\Helpers\MathHelper;
use App\Models\Funding\FundingDeal;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class DealsListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'deals';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [

            TD::make('id', 'ID')
                ->width(20)
                ->render(fn(FundingDeal $funding) => Link::make((string)$funding->id)
                    ->rawClick()
                    ->route('platform.trading.funding_deal.edit', $funding)
                ),

            TD::make('currency', 'Пара')
                ->render(function (FundingDeal $funding) {
                    return Link::make($funding->currency->name)
                        ->rawClick()
                        ->icon('share-alt')
                        ->route('platform.trading.funding_deal.edit', $funding);
                }),


            TD::make('error', 'error')
                ->render(fn(FundingDeal $funding) => $funding->error
                    ? '<i class="text-success">●</i> ' : '<i class="text-danger">●</i> '
                ),


            TD::make('funding_time', 'Funding time')
                ->render(function (FundingDeal $funding) {
                    return $funding->funding_time->toDateTimeString();
                }),

            TD::make('status', 'Статус')
                ->render(function (FundingDeal $funding) {
                    return $funding->statusName;
                }),

            TD::make('created_at', 'Дата создания')
                ->defaultHidden()
                ->render(function (FundingDeal $funding) {
                    return $funding->created_at->toDateTimeString();
                }),

            TD::make('funding_rate', 'rate'),
            TD::make('entry_price', 'Цена входа'),
            TD::make('exit_price', 'Цена выхода'),

            TD::make('diff_percent', '% изменения')
                ->render(function (FundingDeal $funding) {
                    if (!$funding->entry_price) {
                        return '';
                    }

                    $change = MathHelper::getPercentOfNumber($funding->entry_price, $funding->exit_price);
                    $color = $change > 0 ? 'green' : ($change < 0 ? 'red' : 'inherit');

                    return sprintf(
                        ' <small style="color: %s">%+.1f%%</small>',
                        $color,
                        $change
                    );
                }),
            TD::make('funding_fee', 'fee')
                ->render(function (FundingDeal $funding) {
                    return MathHelper::formatNumber($funding->funding_fee);
                }),
            TD::make('total_pnl', 'pnl')
                ->render(function (FundingDeal $funding) {
                    return MathHelper::formatNumber($funding->total_pnl);
                }),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(FundingDeal $funding) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make('Перейти в валюту')
                            ->route('platform.currencies.edit', $funding->currency_id)
                            ->target('_blank')
                            ->icon('dollar'),

                        Link::make('Открыть TradingView')
                            ->icon('grid')
                            ->target('_blank')
                            ->rawClick()
                            ->href($funding->currency->getTVLink()),

                        Link::make(__('Изменить'))
                            ->rawClick()
                            ->route('platform.trading.funding_deal.edit', $funding->id)
                            ->icon('bs.pencil'),

//                        Button::make(__('Delete'))
//                            ->icon('bs.trash3')
//                            ->confirm('Вы уверены?')
//                            ->method('remove', [
//                                'id' => $funding->id,
//                            ]),
                    ])),



        ];
    }
}
