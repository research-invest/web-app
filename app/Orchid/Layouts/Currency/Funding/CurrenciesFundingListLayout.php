<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Currency\Funding;

use App\Helpers\MathHelper;
use App\Models\Currency;
use Carbon\Carbon;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class CurrenciesFundingListLayout extends Table
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
                ->render(function (Currency $currency) {
                    return Group::make([

                        Link::make($currency->name)
                            ->route('platform.currencies.edit', $currency->id)
                            ->rawClick(),

                        Link::make('')
                            ->icon('share-alt')
                            ->target('_blank')
                            ->rawClick()
                            ->href($currency->getExchangeLink()),
                    ])->alignBaseLine()->alignCenter();
                }),

            TD::make('code', 'Код'),

            TD::make('funding_rate', 'Funding')
                ->sort()
                ->render(fn(Currency $currency) => $currency->funding_rate),


            TD::make('next_settle_time', 'Следующее изменение')
                ->render(function(Currency $currency) {
//                    $timestamp = $currency->latestFundingRate->next_settle_time / 1000;
                    $timestamp = $currency->next_settle_time ? : $currency->latestFundingRate->next_settle_time / 1000;
                    $nextSettleTime = is_object($currency->next_settle_time) ? $currency->next_settle_time : Carbon::createFromTimestamp($timestamp);

                    // Расчет оставшегося времени
                    $remainingTime = now()->diff($nextSettleTime);
                    $totalHours = $remainingTime->h + ($remainingTime->d * 24);
                    $remaining = sprintf(
                        '%02dч %02dм',
                        $totalHours,
                        $remainingTime->i
                    );

                    $colorClass = 'text-muted';
                    if ($totalHours < 1) {
                        $colorClass = 'text-danger';
                    } elseif ($totalHours < 2) {
                        $colorClass = 'text-warning';
                    }

                    return sprintf(
                        '%s UTC<br>%s MSK<br><span class="%s">осталось: %s</span>',
                        $currency->latestFundingRate->next_settle_time_utc,
                        $currency->latestFundingRate->next_settle_time_msk,
                        $colorClass,
                        $remaining
                    );
                })
                ->alignLeft(),

//            TD::make('max_funding_rate', 'Max funding')
//                ->defaultHidden()
//                ->render(fn (Currency $currency) => $currency->latestFundingRate->max_funding_rate),
//
//            TD::make('max_funding_rate', 'Min funding')
//                ->defaultHidden()
//                ->render(fn (Currency $currency) => $currency->latestFundingRate->min_funding_rate),
//
//            TD::make('max_funding_rate', 'Collect cycle')
//                ->render(fn (Currency $currency) => $currency->latestFundingRate->collect_cycle),

            TD::make('start_funding_8h', 'Funding 8h')
                ->defaultHidden()
                ->sort(),

            TD::make('start_funding_24h', 'Funding 12h')
                ->defaultHidden()
                ->sort(),

            TD::make('start_funding_48h', 'Funding 48h')
                ->defaultHidden()
                ->sort(),

            TD::make('start_funding_7d', 'Funding 7d')
                ->defaultHidden()
                ->sort(),

            TD::make('start_funding_30d', 'Funding 30d')
                ->defaultHidden()
                ->sort(),

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
