<?php

namespace Botble\FreeGifts\Services;

use Botble\FreeGifts\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class FreeGiftsSettingService
{
    protected array $defaultSettings = [
        'display_mode' => 'popup',
        'display_type' => 'table',
        'hide_gift_products_in_shop' => true,
        'allow_multiple_gift_quantities' => false,
        'allow_remove_auto_gifts' => true,
        'charge_shipping_for_gifts' => false,
        'log_retention_days' => 30,
        'eligibility_notice_enabled' => true,
        'eligibility_notice_text' => 'You are eligible for free gifts! Click here to choose your gifts.',
        'gift_selection_title' => 'Select Your Free Gifts',
        'gift_selection_description' => 'You can choose from the following free gifts:',
        'add_gift_button_text' => 'Add Gift',
        'remove_gift_button_text' => 'Remove Gift',
        'gift_added_text' => 'Gift added to your cart!',
    ];

    public function getSettings(): array
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        
        return array_merge($this->defaultSettings, $settings);
    }

    public function getSetting(string $key, $default = null)
    {
        $settings = $this->getSettings();
        
        return Arr::get($settings, $key, $default);
    }

    public function saveSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    public function deleteSettings(array $keys): void
    {
        Setting::whereIn('key', $keys)->delete();
    }
}
