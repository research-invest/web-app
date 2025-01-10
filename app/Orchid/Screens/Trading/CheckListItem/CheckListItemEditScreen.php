<?php

namespace App\Orchid\Screens\Trading\CheckListItem;

use App\Helpers\UserHelper;
use App\Models\CheckListItem;
use App\Models\Strategy;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class CheckListItemEditScreen extends Screen
{
    public $checkListItem;

    public function query(CheckListItem $checkListItem): array
    {
        return [
            'checkListItem' => $checkListItem
        ];
    }

    public function name(): ?string
    {
        return $this->checkListItem->exists ? 'Редактировать пункт' : 'Создать пункт';
    }

    public function layout(): array
    {
        return [
            Layout::rows([
                Input::make('checkListItem.title')
                    ->title('Название')
                    ->required(),

                TextArea::make('checkListItem.description')
                    ->title('Описание')
                    ->rows(3),

                Relation::make('checkListItem.trade_strategy_id')
                    ->title('Стратегия')
                    ->fromModel(Strategy::class, 'name'),

                Input::make('checkListItem.priority')
                    ->title('Приоритет')
                    ->type('number')
                    ->value(0),

                Input::make('checkListItem.sort_order')
                    ->title('Порядок сортировки')
                    ->type('number')
                    ->value(0),

                Button::make('Сохранить')
                    ->method('save')
                    ->icon('save')
                    ->class('btn btn-primary'),
            ])
        ];
    }

    public function save(CheckListItem $checkListItem, Request $request)
    {
        $data = $request->get('checkListItem');

        $checkListItem->fill($data)
            ->fill([
                'user_id' => UserHelper::getId(),
            ])->save();

        Toast::info('Пункт чек-листа сохранен');

        return redirect()->route('platform.trading.check-item.edit', $checkListItem->id);
    }
}
