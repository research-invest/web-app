<?php

namespace App\Orchid\Screens\Trading;

use App\Helpers\UserHelper;
use App\Models\Strategy;
use App\Orchid\Layouts\Trading\Strategies\ListLayout;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class StrategiesScreen extends Screen
{
    public function query(): array
    {
        return [
            'strategies' => Strategy::latest()->byCreator()->paginate()
        ];
    }

    public function name(): ?string
    {
        return 'Торговые стратегии';
    }

    public function layout(): array
    {
        return [

            ListLayout::class,

            Layout::rows([
                Input::make('strategy.name')
                    ->title('Название')
                    ->required(),

                TextArea::make('strategy.description')
                    ->title('Описание')
                    ->required(),

                Button::make('Сохранить')
                    ->method('createOrUpdate')
                    ->class('btn btn-primary')
            ])
        ];
    }

    public function createOrUpdate(Strategy $strategy): void
    {
        $strategy
            ->fill(request()->get('strategy'))
            ->fill([
                'user_id' => UserHelper::getId(),
            ])
            ->save();
    }
}
