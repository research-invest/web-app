<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Settings;

use App\Models\Setting;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Support\Color;

class ListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'settings';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [

            TD::make('key', 'Ключ')
                ->width('200')
                ->align(TD::ALIGN_RIGHT),

            TD::make('description', 'Описание'),

            TD::make('value', 'Значение')
                ->width('300')
                ->render(function (Setting $setting) {

                    if ($setting->isTypeBool()) {
                        return Button::make($setting->value === '1' ? 'Включено' : 'Выключено')
                            ->icon('bs.check-circle')
                            ->method('toggleSettingActive')
                            ->novalidate()
                            ->type($setting->value === '1' ? Color::SUCCESS() : Color::ERROR())
                            ->parameters([
                                'setting' => $setting->id
                            ]);
                    }

                    return ModalToggle::make($setting->value)
                        ->icon('arrow-right')
                        ->modal('settingsValueModal')
                        ->modalTitle('Изменить настройку')
                        ->method('saveSetting')
                        ->asyncParameters([
                            'setting' => $setting->id,
                        ]);
                }),
        ];
    }
}
