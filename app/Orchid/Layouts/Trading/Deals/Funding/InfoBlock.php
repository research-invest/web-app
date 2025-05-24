<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Trading\Deals\Funding;

use App\Helpers\MathHelper;
use App\Models\Funding\FundingDeal;
use Orchid\Screen\Layouts\Legend;
use Orchid\Screen\Sight;

class InfoBlock extends Legend
{
    protected $target = 'deal';
    protected $title = 'Информация';

    protected function columns(): iterable
    {
        /** @var FundingDeal $deal */
        $trade = $this->query->get('deal');

        return [

            Sight::make('created_at', 'Дата создания')
                ->render(fn(FundingDeal $trade) => $trade->created_at->format('Y-m-d H:i:s')),

            Sight::make('funding_time', 'Funding time')
                ->render(fn(FundingDeal $trade) => $trade->funding_time->toDateTimeString()),

            Sight::make('run_time', 'Run time')
                ->render(fn(FundingDeal $trade) => $trade->run_time->toDateTimeString()),

            Sight::make('funding_rate', 'Rate')
                ->render(fn(FundingDeal $trade) => $trade->funding_rate),

            Sight::make('status', 'Статус')
                ->render(fn(FundingDeal $trade) => $trade->statusName),

            Sight::make('entry_price', 'Цена входа'),
            Sight::make('exit_price', 'Цена выхода'),
            Sight::make('profit_loss', 'Профит'),
            Sight::make('position_size', 'Размер позиции'),
            Sight::make('contract_quantity', 'Количество контрактов'),
            Sight::make('leverage', 'Плечо'),
            Sight::make('initial_margin', 'Начальная сумма'),
            Sight::make('funding_fee', 'Комиссия фандинга'),
            Sight::make('pnl_before_funding', 'pnl_before_funding'),
            Sight::make('total_pnl', 'pnl'),
            Sight::make('pnl_percent', 'pnl %')->render(function (FundingDeal $trade) {
                return MathHelper::getPercentOfNumber($trade->initial_margin, $trade->total_pnl);
            }),
            Sight::make('roi_percent', 'roi'),
            Sight::make('pre_funding_volatility', 'Индекс волатильности'),
            Sight::make('error', 'Ошибка'),
            Sight::make('comment', 'Комментарий'),
        ];
    }

}
