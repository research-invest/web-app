<?php

namespace App\Orchid\Screens\Trading;

use App\Helpers\UserHelper;
use App\Models\TradePeriod;
use App\Orchid\Layouts\Trading\Periods\ListLayout;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TradingPeriodScreen extends Screen
{
    public function query(): array
    {
        return [
            'periods' => TradePeriod::latest()->byCreator()->paginate()
        ];
    }

    public function name(): ?string
    {
        return 'Торговые периоды';
    }

    public function layout(): array
    {
        return [

            ListLayout::class,

            Layout::rows([
                Input::make('period.name')
                    ->title('Название периода')
                    ->required(),

                DateTimer::make('period.start_date')
                    ->title('Дата начала')
                    ->format('Y-m-d')
                    ->required(),

                DateTimer::make('period.end_date')
                    ->title('Дата окончания')
                    ->format('Y-m-d')
                    ->required(),

                Input::make('period.daily_target')
                    ->title('Дневная цель')
                    ->type('number')
                    ->value(100)
                    ->step(1)
                    ->required(),

                Input::make('period.weekend_target')
                    ->title('Цель для выходных')
                    ->type('number')
                    ->value(50)
                    ->step(1)
                    ->required(),

                Input::make('period.deposit')
                    ->title('Плановый депозит')
                    ->value(2000)
                    ->type('number')
                    ->required(),

                CheckBox::make('period.is_active')
                    ->title('Активный период')
                    ->value(0),

                Button::make('Сохранить')
                    ->method('createOrUpdate')
                    ->class('btn btn-primary')
            ])
        ];
    }

    public function createOrUpdate(TradePeriod $period): void
    {
        $period
            ->fill(request()->get('period'))
            ->fill([
                'user_id' => UserHelper::getId(),
            ])
            ->save();
    }
    public function togglePeriodActive(TradePeriod $period): void
    {
        TradePeriod::query()->update(['is_active' => false]);

        $period->update(['is_active' => true]);

        Toast::info('Период успешно активирован');
    }
}
