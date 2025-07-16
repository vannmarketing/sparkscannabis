<?php

namespace Botble\FreeGifts\Http\Requests;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class GiftRuleRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|' . Rule::in(BaseStatusEnum::values()),
            'gift_type' => 'required|string|in:manual,automatic,buy_x_get_y,coupon_based',
            'criteria_type' => 'required|string|in:cart_subtotal,cart_total,category_total,cart_quantity',
            'criteria_value' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'active_days' => 'nullable|array',
            'active_days.*' => 'string|in:mon,tue,wed,thu,fri,sat,sun',
            'max_gifts_per_order' => 'nullable|integer|min:1',
            'max_gifts_per_customer' => 'nullable|integer|min:1',
            'max_gifts_total' => 'nullable|integer|min:1',
            'require_customer_login' => 'boolean',
            'allow_coupon' => 'boolean',
            'require_min_orders' => 'boolean',
            'min_orders_count' => 'nullable|integer|min:1',
            'product_filter_type' => 'nullable|string|in:all,specific_products,specific_categories',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:ec_products,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:ec_product_categories,id',
            'customer_filter_type' => 'nullable|string|in:all,specific_customers',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'integer|exists:ec_customers,id',
            'gift_products' => 'required_if:gift_type,manual,automatic,buy_x_get_y|array',
            'gift_products.*.quantity' => 'required|integer|min:1',
            'gift_products.*.is_same_product' => 'boolean',
        ];
    }
}
