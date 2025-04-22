<?php

namespace App\Orchid\Layouts\BtcWallets;

use App\Helpers\MathHelper;
use App\Models\BtcWallets\Wallet;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class WalletsListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'wallets';

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
                ->render(fn(Wallet $wallet) => Link::make((string)$wallet->id)
                    ->target('_blank')
                    ->route('platform.statistics.btc-wallets.show', $wallet->id)
                    ->rawClick()
                    ->icon('bs.pencil')),

            TD::make('address', 'Адрес')
                ->cantHide()
                ->filter(Input::make())
                ->render(fn(Wallet $wallet) => Link::make($wallet->address)
                    ->target('_blank')
                    ->route('platform.statistics.btc-wallets.show', $wallet->id)
                    ->rawClick()),

            TD::make('balance', 'Баланс')
                ->render(function (Wallet $wallet) {
                    return MathHelper::formatNumber($wallet->balance);
                })
                ->sort(),

            TD::make('diff_percent', 'Diff percent')
                ->sort()
                ->render(function (Wallet $wallet) {
                    $color = $wallet->diff_percent > 0 ? 'green' : ($wallet->diff_percent < 0 ? 'red' : 'inherit');
                    return sprintf(
                        ' <small style="color: %s">%+.1f%%</small>',
                        $color,
                        $wallet->diff_percent,
                    );
                }),

            TD::make('last_price', 'Цена')
                ->render(function (Wallet $wallet) {
                    return MathHelper::formatNumber($wallet->last_price);
                }),

            TD::make('label', 'label')
                ->defaultHidden(),

            TD::make('volatility', 'Динамика')
                ->defaultHidden()
                ->render(function (Wallet $wallet) {
                    $data = (array)$wallet->diff_percent_history;
                    return '<span style="font-family: monospace;">' . MathHelper::renderSparkline($data) . '</span>';
                }),

            TD::make('volatility_index', 'Волатильность')
                ->defaultHidden()
                ->render(function (Wallet $wallet) {
                    $data = (array)$wallet->diff_percent_history;
                    $volatility = MathHelper::calculateVolatility($data);
                    $color = $volatility > 5 ? 'red' : ($volatility > 2 ? 'orange' : 'green');
                    return "<span style='color: $color;'>$volatility%</span>";
                }),

            TD::make('trend', 'Тренд')
                ->defaultHidden()
                ->render(function (Wallet $wallet) {
                    $data = (array)$wallet->diff_percent_history;

                    $last = end($data);
                    $prev = prev($data);

                    if ($last && $prev) {
                        if ($last > $prev) {
                            return '📈 <span style="color:green;">Рост</span>';
                        }

                        if ($last < $prev) {
                            return '📉 <span style="color:red;">Падение</span>';
                        }
                    }

                    return '➖';
                }),

            TD::make('summary', 'Аналитика')
                ->defaultHidden()
                ->render(function (Wallet $wallet) {
                    $data = (array)$wallet->diff_percent_history;
                    if (count($data) < 2) {
                        return '–';
                    }

                    $sparkline = MathHelper::renderSparkline($data);
                    $volatility = MathHelper::calculateVolatility($data);
                    $last = end($data);
                    $prev = prev($data);

                    $trend = $last > $prev ? '📈' : ($last < $prev ? '📉' : '➖');

                    return <<<HTML
<div style="font-family: monospace;">
    <div>$sparkline</div>
    <div>Волат: <b>{$volatility}%</b> $trend</div>
</div>
HTML;
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

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(Wallet $wallet) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make('Открыть')
                            ->route('platform.statistics.btc-wallets.show', $wallet->id)
                            ->rawClick()
                            ->icon('bs.pencil'),

                        Link::make('Обозреватель')
                            ->icon('grid')
                            ->target('_blank')
                            ->rawClick()
                            ->href($wallet->getExplorerLink()),

                    ])),
        ];
    }

    protected function striped(): bool
    {
        return true;
    }
    protected function bordered(): bool
    {
        return true;
    }
    protected function hoverable(): bool
    {
        return true;
    }
}
