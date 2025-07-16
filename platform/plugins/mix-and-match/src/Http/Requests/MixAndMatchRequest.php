<?php

namespace Botble\MixAndMatch\Http\Requests;

use Botble\Base\Facades\BaseHelper;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class MixAndMatchRequest extends Request
{
    public function rules(): array
    {
        return [
            'is_mix_and_match' => 'nullable|boolean',
            'min_container_size' => 'required_if:is_mix_and_match,1|integer|min:1',
            'max_container_size' => 'nullable|integer|min:1|gte:min_container_size',
            'pricing_type' => [
                'required_if:is_mix_and_match,1',
                Rule::in(['fixed_price', 'per_item']),
            ],
            'fixed_price' => 'required_if:pricing_type,fixed_price|nullable|numeric|min:0',
            'mix_and_match_items' => 'required_if:is_mix_and_match,1|array',
            'mix_and_match_items.*.min_qty' => 'required|integer|min:0',
            'mix_and_match_items.*.max_qty' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'mix_and_match_items.required_if' => trans('plugins/mix-and-match::mix-and-match.validation.items_required'),
            'min_container_size.required_if' => trans('plugins/mix-and-match::mix-and-match.validation.min_container_size_required'),
            'pricing_type.required_if' => trans('plugins/mix-and-match::mix-and-match.validation.pricing_type_required'),
            'fixed_price.required_if' => trans('plugins/mix-and-match::mix-and-match.validation.fixed_price_required'),
        ];
    }

    public function attributes(): array
    {
        return [
            'min_container_size' => trans('plugins/mix-and-match::mix-and-match.minimum_container_size'),
            'max_container_size' => trans('plugins/mix-and-match::mix-and-match.maximum_container_size'),
            'pricing_type' => trans('plugins/mix-and-match::mix-and-match.pricing_type'),
            'fixed_price' => trans('plugins/mix-and-match::mix-and-match.fixed_price_amount'),
            'mix_and_match_items' => trans('plugins/mix-and-match::mix-and-match.select_products'),
            'mix_and_match_items.*.min_qty' => trans('plugins/mix-and-match::mix-and-match.min_qty'),
            'mix_and_match_items.*.max_qty' => trans('plugins/mix-and-match::mix-and-match.max_qty'),
        ];
    }
}
