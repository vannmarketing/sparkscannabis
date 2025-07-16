<?php

use Botble\Ecommerce\Facades\Cart;
use Botble\FreeGifts\Models\GiftRule;
use Botble\FreeGifts\Services\FreeGiftsService;
use Botble\FreeGifts\Services\FreeGiftsSettingService;

if (!function_exists('free_gifts')) {
    /**
     * Get the free gifts service instance.
     */
    function free_gifts(): FreeGiftsService
    {
        return app(FreeGiftsService::class);
    }
}

if (!function_exists('free_gifts_settings')) {
    /**
     * Get the free gifts settings service instance.
     */
    function free_gifts_settings(): FreeGiftsSettingService
    {
        return app(FreeGiftsSettingService::class);
    }
}

if (!function_exists('get_eligible_gift_rules')) {
    /**
     * Get all eligible gift rules based on the current cart.
     */
    function get_eligible_gift_rules(): \Illuminate\Support\Collection
    {
        return free_gifts()->getEligibleRules();
    }
}

if (!function_exists('get_eligible_gifts')) {
    /**
     * Get all eligible gifts based on the current cart.
     */
    function get_eligible_gifts(): \Illuminate\Support\Collection
    {
        $eligibleRules = get_eligible_gift_rules();
        $gifts = collect();

        foreach ($eligibleRules as $rule) {
            $ruleGifts = free_gifts()->getEligibleGifts($rule);
            $gifts = $gifts->merge($ruleGifts->map(function ($gift) use ($rule) {
                $gift->gift_rule_id = $rule->id;
                return $gift;
            }));
        }

        return $gifts;
    }
}

if (!function_exists('has_eligible_gifts')) {
    /**
     * Check if there are any eligible gifts based on the current cart.
     */
    function has_eligible_gifts(): bool
    {
        return get_eligible_gifts()->isNotEmpty();
    }
}

if (!function_exists('is_free_gift')) {
    /**
     * Check if a cart item is a free gift.
     */
    function is_free_gift($cartItem): bool
    {
        return isset($cartItem->options['is_free_gift']) && $cartItem->options['is_free_gift'];
    }
}

if (!function_exists('get_gift_items_in_cart')) {
    /**
     * Get all gift items in the cart.
     */
    function get_gift_items_in_cart(): \Illuminate\Support\Collection
    {
        return Cart::instance('cart')->content()->filter(function ($item) {
            return is_free_gift($item);
        });
    }
}

if (!function_exists('get_gift_rule')) {
    /**
     * Get a gift rule by ID.
     */
    function get_gift_rule(int $id): ?GiftRule
    {
        return GiftRule::find($id);
    }
}

if (!function_exists('format_active_days')) {
    /**
     * Format active days for display.
     */
    function format_active_days(?array $activeDays): string
    {
        if (empty($activeDays)) {
            return trans('plugins/free-gifts::gift-rules.all_days');
        }

        $dayLabels = [
            'mon' => trans('plugins/free-gifts::gift-rules.days.monday'),
            'tue' => trans('plugins/free-gifts::gift-rules.days.tuesday'),
            'wed' => trans('plugins/free-gifts::gift-rules.days.wednesday'),
            'thu' => trans('plugins/free-gifts::gift-rules.days.thursday'),
            'fri' => trans('plugins/free-gifts::gift-rules.days.friday'),
            'sat' => trans('plugins/free-gifts::gift-rules.days.saturday'),
            'sun' => trans('plugins/free-gifts::gift-rules.days.sunday'),
        ];

        $days = collect($activeDays)->map(function ($day) use ($dayLabels) {
            return $dayLabels[$day] ?? $day;
        })->implode(', ');

        return $days;
    }
}
