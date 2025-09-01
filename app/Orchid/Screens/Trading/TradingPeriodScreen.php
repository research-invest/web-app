<?php

namespace App\Orchid\Screens\Trading;

use App\Helpers\UserHelper;
use App\Models\TradePeriod;
use App\Orchid\Layouts\Trading\Periods\ListLayout;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
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

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Создать период')
                ->modal('createPeriodModal')
                ->method('createOrUpdate')
                ->icon('plus')
                ->class('btn btn-primary'),

            Link::make('Глобальный отчет')
                ->route('platform.trading.global-report')
                ->icon('bs.bar-chart')
                ->class('btn btn-info'),
        ];
    }

    public function layout(): array
    {
        return [
            ListLayout::class,

            Layout::modal('createPeriodModal', [
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
                        ->value(50)
                        ->step(1)
                        ->required(),

                    Input::make('period.weekend_target')
                        ->title('Цель для выходных')
                        ->type('number')
                        ->value(25)
                        ->step(1)
                        ->required(),

                    Input::make('period.deposit')
                        ->title('Плановый депозит')
                        ->value(2000)
                        ->type('number')
                        ->required(),

                    CheckBox::make('period.is_active')
                        ->sendTrueOrFalse()
                        ->placeholder('Активный период')
                        ->value(0),
                ])
            ])
                ->title('Создать торговый период')
                ->applyButton('Сохранить')
                ->closeButton('Отмена'),
        ];
    }

    public function createOrUpdate(): void
    {
        $period = new TradePeriod();

        $data = request()->get('period');
        $period
            ->fill($data)
            ->fill([
                'user_id' => UserHelper::getId(),
            ])
            ->save();

        if ($period->is_active) {
//            $this->togglePeriodActive($period);
        }

        Toast::success('Торговый период успешно создан');
    }

    public function togglePeriodActive(TradePeriod $period): void
    {
        TradePeriod::query()
            ->byCreator()
            ->update(['is_active' => false]);

        $period->update(['is_active' => true]);

        Toast::info('Период успешно активирован');
    }
}
