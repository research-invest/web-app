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
                    $timestamp = $currency->latestFundingRate->next_settle_time / 1000;
                    $nextSettleTime = Carbon::createFromTimestamp($timestamp);
                    
                    $utc = $nextSettleTime->timezone('UTC')
                        ->format('Y-m-d H:i:s');
                    $msk = $nextSettleTime->timezone('Europe/Moscow')
                        ->format('H:i:s');
                    
                    // Расчет оставшегося времени
                    $remainingTime = now()->diff($nextSettleTime);
                    $remaining = sprintf(
                        '%02dч %02dм',
                        $remainingTime->h + ($remainingTime->d * 24),
                        $remainingTime->i
                    );
                    
                    return sprintf(
                        '%s UTC<br>%s MSK<br><span class="text-muted">осталось: %s</span>', 
                        $utc,
                        $msk,
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
