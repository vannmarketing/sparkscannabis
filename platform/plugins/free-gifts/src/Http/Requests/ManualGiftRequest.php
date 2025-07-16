<?php

namespace Botble\FreeGifts\Http\Requests;

use Botble\Support\Http\Requests\Request;

class ManualGiftRequest extends Request
{
    public function rules(): array
    {
        return [
            'customer_id' => 'required|integer|exists:ec_customers,id',
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:ec_products,id',
            'quantities' => 'required|array',
            'quantities.*' => 'integer|min:1',
        ];
    }
}
