<?php

namespace App\Orchid\Screens\Statistics\BtcWallets;

use App\Models\BtcWallets\Wallet;
use App\Orchid\Layouts\Charts\HighchartsChart;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BtcWalletShowScreen extends Screen
{
    /**
     * @var Wallet
     */
    public $wallet;

    public function query(Wallet $wallet): iterable
    {
        return [
            'wallet' => $wallet,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return $this->wallet->address;
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Обозреватель')
                ->icon('grid')
                ->target('_blank')
                ->rawClick()
                ->href($this->wallet->getExplorerLink()),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'График баланса кошелька' => [
                    new HighchartsChart(
                        $this->getBalancesChart()
                    ),
                ],
                'Основная информация' => [
                    Layout::rows([
                        Select::make('wallet.visible_type')
                            ->empty('Выберите')
                            ->title('Тип позиции')
                            ->value($this->wallet->visible_type)
                            ->options(Wallet::getVisibleTypes()),

                        Select::make('wallet.label_type')
                            ->empty('Выберите')
                            ->title('Тип метки')
                            ->value($this->wallet->label_type)
                            ->options(Wallet::getLabelTypes()),

                        TextArea::make('wallet.label')
                            ->title('Заметки')
                            ->value($this->wallet->label)
                            ->rows(3),

//                        CheckBox::make('wallet.is_notify')
//                            ->placeholder('Уведомления')
//                            ->sendTrueOrFalse()
//                            ->value(1),

                        Button::make('Сохранить')
                            ->icon('save')
                            ->method('save')
                            ->class('btn btn-default'),
                    ])
                ],
            ]),
        ];
    }

    public function save(Wallet $wallet, Request $request)
    {
        $data = $request->get('wallet');

        $wallet->fill($data);
        $wallet->save();

        Toast::success('Кошелек успешно обновлен');
    }

    private function getBalancesChart(): array
    {
        $balances = [];

        foreach ($this->wallet->balances as $balance) {

            $timestamp = $balance->created_at->timestamp * 1000;

            $balances[] = [
                $timestamp,
                (float)$balance->balance,
            ];
        }

        return [
            'chart' => [
                'height' => 600,
                'type' => 'line'
            ],
            'title' => [
                'text' => 'Динамика изменений баланса'
            ],
            'rangeSelector' => [
                'enabled' => false,
            ],
            'navigator' => [
                'enabled' => true,
            ],
            'scrollbar' => [
                'enabled' => true
            ],
            'xAxis' => [
                'type' => 'datetime',
                'labels' => [
                    'format' => '{value:%Y-%m-%d %H:%M}'  //  '{value:%d.%m %H:%M}'
                ]
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Изменение'
                ],
                'labels' => [
                    'format' => '{value}'
                ]
            ],
            'series' => [
                [
                    'name' => 'Изменение баланса',
                    'data' => $balances,
                    'color' => '#7cb5ec',
                    'tooltip' => [
                        'valueSuffix' => '',
                        'valueDecimals' => 2
                    ]
                ],
            ],
            'plotOptions' => [
                'series' => [
                    'marker' => [
                        'enabled' => true,
                        'radius' => 3
                    ]
                ]
            ]
        ];
    }
}
