<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Trading\Deals;

use App\Models\BankCard\BankCard;
use App\Models\Currency\Currency;
use App\Repositories\Currency\CurrencyRepository;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\TD;

class FormLayout extends Rows
{
    /**
     * @return TD[]
     */
    public function fields(): array
    {
        /** @var BankCard $card */
        $card = $this->query->get('trade');

        return [
            Input::make('card.card')
                ->required()
                ->title('Номер карты (или телефона если СБП)'),

            Input::make('card.fio')
                ->required()
                ->title('ФИО'),

            Select::make('card.currency_id')
                ->canSee($isShowFields)
                ->required()
                ->options((new CurrencyRepository())->getListByCategories([
                    Currency::CATEGORY_BANK_CARD_RUB,
                    Currency::CATEGORY_BANK_CARD_OTHER,
                ]))
                ->title('Выберите к какому банку привязывать карту'),

            Input::make('card.sbp_bank_name')
                ->title('Название банка для СБП')
            ->help('Название будет отображаться в блоке выдачи реквизитов'),

            TextArea::make('card.description')
                ->title('Описание'),

            Select::make('card.status')
                ->canSee($isShowFields)
                ->options(BankCard::STATUSES)
                ->title('Статус'),

            CheckBox::make('card.is_active')
                ->canSee($isShowFields)
                ->placeholder('Активна?')
                ->sendTrueOrFalse(),

        ];
    }

}
