<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Currency;

use App\Helpers\MathHelper;
use App\Models\Currency;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class CurrenciesListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'currencies';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn(Currency $currency) => Link::make((string)$currency->id)
                    ->route('platform.currencies.edit', $currency->id)
                    ->rawClick()
                    ->icon('bs.pencil')),

            TD::make('name', __('Name'))
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn(Currency $currency) => Link::make($currency->name)
                    ->route('platform.currencies.edit', $currency->id)
                    ->rawClick()),

            TD::make('code', 'Код'),

            TD::make('type', 'Тип')
                ->defaultHidden()
                ->render(fn(Currency $currency) => $currency->type_name),


            TD::make('last_price', 'Цена')
                ->sort()
                ->render(function (Currency $currency) {
                    if (!$currency->last_price) {
                        return '';
                    }

                    $periods = [
                        '24H' => $currency->start_price_24h,
                        '4H' => $currency->start_price_4h,
                        '1H' => $currency->start_price_1h
                    ];

                    $html = MathHelper::formatNumber($currency->last_price);

                    foreach ($periods as $label => $startPrice) {
                        if ($startPrice > 0) {
//                            $change = ($currency->last_price - $startPrice) / $startPrice * 100;
                            $change = MathHelper::getPercentOfNumber($startPrice, $currency->last_price);
                            $color = $change > 0 ? 'green' : ($change < 0 ? 'red' : 'inherit');
                            $html .= sprintf(
                                ' <small style="color: %s">%s: %+.1f%%</small>',
                                $color,
                                $label,
                                $change
                            );
                        }
                    }

                    return $html;
                }),

            TD::make('volume', 'Объем')
                ->sort()
                ->render(function (Currency $currency) {

                    if (!$currency->volume) {
                        return '';
                    }


                    $current = $currency->volume;
                    $html = [];

                    // Форматируем текущий объем
                    $html[] = sprintf(
                        '%s (%s)',
                        MathHelper::humanNumber($current),
                        MathHelper::formatNumber($current)
                    );

                    // Добавляем изменения за разные периоды
                    $periods = [
                        '1H' => $currency->start_volume_1h,
                        '4H' => $currency->start_volume_4h,
                        '24H' => $currency->start_volume_24h
                    ];

                    foreach ($periods as $label => $startVolume) {
                        if ($startVolume > 0) {
                            $change = ($current - $startVolume) / $startVolume * 100;
                            $color = $change > 0 ? 'green' : ($change < 0 ? 'red' : 'inherit');

                            $html[] = sprintf(
                                '<div style="color: %s">%s: %+.2f%%</div>',
                                $color,
                                $label,
                                $change
                            );
                        }
                    }

                    return implode('', $html);
                }),

            TD::make('created_at', __('Created'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort(),

            TD::make('updated_at', __('Last edit'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort(),

            TD::make('exchange', 'Источник')
                ->defaultHidden(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(Currency $currency) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make('Открыть TradingView')
                            ->icon('grid')
                            ->target('_blank')
                            ->rawClick()
                            ->href($currency->getTVLink()),

                        Link::make('Открыть на бирже')
                            ->icon('grid')
                            ->target('_blank')
                            ->rawClick()
                            ->href($currency->getExchangeLink()),

                        Link::make('Корреляция с BTC/ETH')
                            ->icon('grid')
                            ->target('_blank')
                            ->rawClick()
                            ->route('platform.statistics.crypto-correlation.details', [
                                'currency' => $currency->id
                            ]),

                        Link::make(__('Edit'))
                            ->route('platform.currencies.edit', $currency->id)
                            ->rawClick()
                            ->icon('bs.pencil'),

                    ])),
        ];
    }

    protected function striped(): bool
    {
        return true;
    }
}
