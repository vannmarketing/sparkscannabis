<?php

namespace Botble\FreeGifts\Http\Requests;

use Botble\Support\Http\Requests\Request;

class FreeGiftsSettingRequest extends Request
{
    public function rules(): array
    {
        return [
            'display_mode' => 'required|string|in:inline,popup',
            'display_type' => 'required|string|in:table,carousel,dropdown',
            'hide_gift_products_in_shop' => 'boolean',
            'allow_multiple_gift_quantities' => 'boolean',
            'allow_remove_auto_gifts' => 'boolean',
            'charge_shipping_for_gifts' => 'boolean',
            'log_retention_days' => 'nullable|integer|min:1',
            'eligibility_notice_enabled' => 'boolean',
            'eligibility_notice_text' => 'nullable|string',
            'gift_selection_title' => 'nullable|string',
            'gift_selection_description' => 'nullable|string',
            'add_gift_button_text' => 'nullable|string',
            'remove_gift_button_text' => 'nullable|string',
            'gift_added_text' => 'nullable|string',
        ];
    }
}
