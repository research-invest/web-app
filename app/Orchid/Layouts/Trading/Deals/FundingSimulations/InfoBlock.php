<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Trading\Deals\FundingSimulations;

use App\Models\FundingSimulation;
use Orchid\Screen\Layouts\Legend;
use Orchid\Screen\Sight;

class InfoBlock extends Legend
{
    protected $target = 'trade';
    protected $title = 'Информация';

    protected function columns(): iterable
    {
        /** @var FundingSimulation $trade */
        $trade = $this->query->get('trade');

        return [

            Sight::make('created_at', 'Дата создания')
                ->render(fn(FundingSimulation $trade) => $trade->created_at->format('Y-m-d H:i:s')),

            Sight::make('funding_time', 'Funding time')
                ->render(fn(FundingSimulation $trade) => $trade->funding_time->toDateTimeString()),

            Sight::make('funding_rate', 'Rate')
                ->render(fn(FundingSimulation $trade) => $trade->funding_rate),

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
            Sight::make('roi_percent', 'roi'),
            Sight::make('pre_funding_volatility', 'Индекс волатильности'),
        ];
    }

}
