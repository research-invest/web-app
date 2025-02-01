<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Settings;

use App\Models\Setting;
use App\Orchid\Layouts\Settings\FormValueLayout;
use App\Orchid\Layouts\Settings\ListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SettingsScreen extends Screen
{

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'settings' =>  Setting::filters()
                ->paginate(30),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Настройки';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return '';
    }

    public function permission(): ?iterable
    {
        return [
//            Role::PERMISSION_SETTINGS,
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return string[]|\Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            Layout::modal('settingsValueModal', [
                FormValueLayout::class,
            ])
                ->async('asyncGetSetting')
                ->applyButton('Сохранить')
                ->closeButton('Закрыть'),

//            FiltersLayout::class,
            ListLayout::class,
        ];
    }


    /**
     * @param Setting $setting
     * @return array
     */
    public function asyncGetSetting(Setting $setting): iterable
    {
        return [
            'setting' => $setting,
        ];
    }

    public function saveSetting(Setting $setting, Request $request)
    {
        $setting
            ->fill($request->collect('setting')->toArray())
            ->save();

        $setting->refresh();

        Toast::info(__('Setting was saved.'));

        return redirect()->route('platform.settings.list');
    }


    public function toggleSettingActive(Setting $setting): void
    {
        $setting->update(['value' => $setting->value === '1' ? '0' : '1']);

        Toast::info('Настройка изменена');
    }


}
