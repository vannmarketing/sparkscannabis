<?php

namespace Botble\FreeGifts\Http\Middleware;

use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Models\Product;
use Botble\FreeGifts\Models\GiftRule;
use Botble\FreeGifts\Services\FreeGiftsService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class ProcessFreeGiftsMiddleware
{
    public function __construct(protected FreeGiftsService $freeGiftsService)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        // Only process on specific routes
        if (!$this->shouldProcess($request)) {
            return $next($request);
        }

        // Process automatic gifts
        $this->processAutomaticGifts();

        // Validate manual gifts
        $this->validateManualGifts();

        return $next($request);
    }

    protected function shouldProcess(Request $request): bool
    {
        $routes = [
            'public.cart',
            'public.checkout.information',
            'public.checkout.post-information',
            'public.checkout.process',
            'public.ajax.cart.update',
            'public.ajax.cart.add-to-cart',
            'public.ajax.cart.remove-from-cart',
        ];

        return in_array($request->route()->getName(), $routes);
    }

    protected function processAutomaticGifts(): void
    {
        // Get all eligible rules
        $eligibleRules = $this->freeGiftsService->getEligibleRules();

        // Filter rules for automatic gifts
        $automaticRules = $eligibleRules->filter(function ($rule) {
            return $rule->gift_type === 'automatic';
        });

        // Get current gift items in cart
        $giftItemsInCart = get_gift_items_in_cart();

        // Process each automatic rule
        foreach ($automaticRules as $rule) {
            // Skip if rule has reached max gifts per order
            if ($rule->max_gifts_per_order) {
                $giftCountForRule = $giftItemsInCart
                    ->filter(function ($item) use ($rule) {
                        return isset($item->options['gift_rule_id']) && $item->options['gift_rule_id'] == $rule->id;
                    })
                    ->sum('qty');

                if ($giftCountForRule >= $rule->max_gifts_per_order) {
                    continue;
                }
            }

            // Get eligible gifts for this rule
            $eligibleGifts = $this->freeGiftsService->getEligibleGifts($rule);

            // Add gifts to cart if not already in cart
            foreach ($eligibleGifts as $gift) {
                $giftInCart = $giftItemsInCart->first(function ($item) use ($gift, $rule) {
                    return $item->id == $gift->id && 
                           isset($item->options['gift_rule_id']) && 
                           $item->options['gift_rule_id'] == $rule->id;
                });

                if (!$giftInCart) {
                    $this->freeGiftsService->addGiftToCart($gift, 1, $rule);
                }
            }
        }
    }

    protected function validateManualGifts(): void
    {
        // Get all gift items in cart
        $giftItemsInCart = get_gift_items_in_cart();

        // Get all eligible rules
        $eligibleRules = $this->freeGiftsService->getEligibleRules();

        // Validate each gift item
        foreach ($giftItemsInCart as $rowId => $item) {
            $isValid = false;

            // Check if it's a rule-based gift
            if (isset($item->options['gift_rule_id'])) {
                $ruleId = $item->options['gift_rule_id'];
                $rule = $eligibleRules->firstWhere('id', $ruleId);

                if ($rule) {
                    // Check if the product is eligible for this rule
                    $eligibleGifts = $this->freeGiftsService->getEligibleGifts($rule);
                    $isEligible = $eligibleGifts->contains('id', $item->id);

                    if ($isEligible) {
                        // Check if the quantity is valid
                        if ($rule->max_gifts_per_order) {
                            $giftCountForRule = $giftItemsInCart
                                ->filter(function ($cartItem) use ($rule) {
                                    return isset($cartItem->options['gift_rule_id']) && $cartItem->options['gift_rule_id'] == $rule->id;
                                })
                                ->sum('qty');

                            if ($giftCountForRule <= $rule->max_gifts_per_order) {
                                $isValid = true;
                            }
                        } else {
                            $isValid = true;
                        }
                    }
                }
            } else {
                // Check if it's a manual gift (no rule_id)
                // Manual gifts are always valid as they are added by admin
                $isValid = true;
            }

            // Remove invalid gifts
            if (!$isValid) {
                Cart::instance('cart')->remove($rowId);
            }
        }
    }
}
