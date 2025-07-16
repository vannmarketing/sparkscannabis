<?php

namespace Botble\FreeGifts\Services;

use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Product;
use Botble\FreeGifts\Models\GiftLog;
use Botble\FreeGifts\Models\GiftRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class FreeGiftsService
{
    public function __construct(protected FreeGiftsSettingService $settingService)
    {
    }

    public function getEligibleRules(): Collection
    {
        $rules = GiftRule::query()
            ->where('status', 'published')
            ->get();

        return $rules->filter(function ($rule) {
            return $this->isRuleEligible($rule);
        });
    }

    public function isRuleEligible(GiftRule $rule): bool
    {
        if (!$rule->isActive()) {
            return false;
        }

        // Check if customer login is required
        if ($rule->require_customer_login && !Auth::guard('customer')->check()) {
            return false;
        }

        // Check if coupon is allowed
        if (!$rule->allow_coupon && Cart::instance('cart')->hasAnyDiscount()) {
            return false;
        }

        // Check if customer has minimum required orders
        if ($rule->require_min_orders && Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();
            $orderCount = $customer->orders()->count();
            if ($orderCount < $rule->min_orders_count) {
                return false;
            }
        }

        // Check customer filter
        if ($rule->customer_filter_type === 'specific_customers' && Auth::guard('customer')->check()) {
            $customerId = Auth::guard('customer')->id();
            if (!in_array($customerId, $rule->customer_ids ?? [])) {
                return false;
            }
        }

        // Check criteria
        $cartItems = Cart::instance('cart')->content();
        $cartSubtotal = Cart::instance('cart')->rawSubTotal();
        $cartTotal = Cart::instance('cart')->rawTotal();
        $cartQuantity = $cartItems->sum('qty');

        switch ($rule->criteria_type) {
            case 'cart_subtotal':
                return $cartSubtotal >= $rule->criteria_value;
            case 'cart_total':
                return $cartTotal >= $rule->criteria_value;
            case 'cart_quantity':
                return $cartQuantity >= $rule->criteria_value;
            case 'category_total':
                if (empty($rule->category_ids)) {
                    return false;
                }
                
                $categoryTotal = 0;
                foreach ($cartItems as $item) {
                    $product = Product::find($item->id);
                    if ($product && $product->categories()->whereIn('id', $rule->category_ids)->exists()) {
                        $categoryTotal += $item->qty * $item->price;
                    }
                }
                
                return $categoryTotal >= $rule->criteria_value;
            default:
                return false;
        }
    }

    public function getEligibleGifts(GiftRule $rule): Collection
    {
        return $rule->giftProducts;
    }

    public function addGiftToCart(Product $product, int $quantity = 1, GiftRule $rule = null): bool
    {
        $options = [
            'is_free_gift' => true,
            'gift_rule_id' => $rule?->id,
        ];

        Cart::instance('cart')->add($product->id, $product->name, $quantity, 0, $options);

        return true;
    }

    public function removeGiftFromCart(string $rowId): bool
    {
        Cart::instance('cart')->remove($rowId);

        return true;
    }

    public function logGiftOrder(int $orderId, int $customerId = null): void
    {
        $cartItems = Cart::instance('cart')->content();
        
        foreach ($cartItems as $item) {
            if (isset($item->options['is_free_gift']) && $item->options['is_free_gift']) {
                GiftLog::create([
                    'gift_rule_id' => $item->options['gift_rule_id'] ?? null,
                    'order_id' => $orderId,
                    'customer_id' => $customerId,
                    'product_id' => $item->id,
                    'quantity' => $item->qty,
                    'gift_type' => $item->options['gift_rule_id'] ? 'rule' : 'manual',
                    'is_manual' => empty($item->options['gift_rule_id']),
                ]);
            }
        }
    }
}
