<?php

namespace App\Orchid\Screens\Trading\CheckListItem;

use App\Models\CheckListItem;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class CheckItemListScreen extends Screen
{
    public function query(): array
    {
        return [
            'checkListItems' => CheckListItem::with('strategy')
                ->defaultSort('sort_order')
                ->paginate(),
        ];
    }

    public function name(): ?string
    {
        return 'Пункты чек-листа';
    }

    public function commandBar(): array
    {
        return [
            Link::make('Создать пункт')
                ->icon('plus')
                ->route('platform.trading.check-item.create'),
        ];
    }

//platform.trading.check-list
//platform.trading.check-item.create
//platform.trading.check-item.edit

    public function layout(): array
    {
        return [
            Layout::table('checkListItems', [
                TD::make('sort_order', '№')->sort(),
                TD::make('title', 'Название')->sort(),
                TD::make('strategy.name', 'Стратегия'),
                TD::make('priority', 'Приоритет')->sort(),
                TD::make('created_at', 'Создан')
                    ->render(fn (CheckListItem $model) => $model->created_at->format('d.m.Y')),
                TD::make('action', 'Действия')
                    ->render(function ($model) {
                        return Link::make('Редактировать')
                            ->route('platform.trading.check-item.edit', $model);
                    }),
            ]),
        ];
    }
}
