<?php

namespace Botble\MixAndMatch\Supports;

use Botble\Base\Facades\BaseHelper;
use Botble\Ecommerce\Cart\CartItem;
use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Models\Product;
use Botble\MixAndMatch\Models\MixAndMatchProduct;
use Botble\MixAndMatch\Models\MixAndMatchSetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class MixAndMatchCartSupport
{
    /**
     * Add a Mix and Match product to the cart
     *
     * @param Request $request
     * @return array
     */
    public function addToCart(Request $request): array
    {
        $productId = $request->input('id');
        
        if (!$productId) {
            return [
                'error' => true,
                'message' => trans('plugins/ecommerce::cart.add_to_cart_failed'),
            ];
        }

        $product = Product::query()->find($productId);

        if (!$product) {
            return [
                'error' => true,
                'message' => trans('plugins/ecommerce::cart.add_to_cart_failed'),
            ];
        }

        // Check if product is a Mix and Match product
        $mixAndMatchSetting = MixAndMatchSetting::query()->where('product_id', $product->id)->first();
        
        if (!$mixAndMatchSetting) {
            return [
                'error' => true,
                'message' => trans('plugins/ecommerce::cart.add_to_cart_failed'),
            ];
        }

        $selectedItems = $request->input('mix_and_match_items', []);
        
        if (!$selectedItems || !is_array($selectedItems)) {
            return [
                'error' => true,
                'message' => trans('plugins/mix-and-match::mix-and-match.validation.items_required'),
            ];
        }

        // Calculate total selected items
        $totalSelected = array_sum($selectedItems);
        
        // Validate minimum and maximum container size
        if ($totalSelected < $mixAndMatchSetting->min_container_size) {
            return [
                'error' => true,
                'message' => trans('plugins/mix-and-match::mix-and-match.validation.min_items_required', ['min' => $mixAndMatchSetting->min_container_size]),
            ];
        }

        if ($mixAndMatchSetting->max_container_size && $totalSelected > $mixAndMatchSetting->max_container_size) {
            return [
                'error' => true,
                'message' => trans('plugins/mix-and-match::mix-and-match.validation.max_items_allowed', ['max' => $mixAndMatchSetting->max_container_size]),
            ];
        }

        // Validate selected items against available mix and match items
        $availableItems = MixAndMatchProduct::query()
            ->where('container_product_id', $product->id)
            ->get()
            ->keyBy('child_product_id')
            ->toArray();

        foreach ($selectedItems as $childProductId => $quantity) {
            // Check if item is available for this Mix and Match product
            if (!isset($availableItems[$childProductId])) {
                return [
                    'error' => true,
                    'message' => trans('plugins/ecommerce::cart.add_to_cart_failed'),
                ];
            }

            // Check min and max quantity
            $minQty = $availableItems[$childProductId]['min_qty'];
            $maxQty = $availableItems[$childProductId]['max_qty'];

            if ($quantity < $minQty) {
                return [
                    'error' => true,
                    'message' => trans('plugins/ecommerce::cart.add_to_cart_failed'),
                ];
            }

            if ($quantity > $maxQty) {
                return [
                    'error' => true,
                    'message' => trans('plugins/ecommerce::cart.add_to_cart_failed'),
                ];
            }
        }

        // Calculate price
        $price = $this->calculatePrice($product, $mixAndMatchSetting, $selectedItems);

        try {
            // Add to cart
            $cartItem = Cart::instance('cart')->add(
                $product->id,
                $product->name,
                1,
                $price,
                [
                    'image' => $product->image,
                    'attributes' => [],
                    'product_id' => $product->id,
                    'options' => [
                        'mix_and_match_items' => $selectedItems,
                    ],
                ]
            );

            return [
                'error' => false,
                'message' => trans('plugins/mix-and-match::mix-and-match.product_added_to_cart'),
                'data' => [
                    'count' => Cart::instance('cart')->count(),
                    'total_price' => format_price(Cart::instance('cart')->rawTotal()),
                ],
            ];
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            
            return [
                'error' => true,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Calculate the price for a Mix and Match product
     *
     * @param Product $product
     * @param MixAndMatchSetting $mixAndMatchSetting
     * @param array $selectedItems
     * @return float
     */
    protected function calculatePrice(Product $product, MixAndMatchSetting $mixAndMatchSetting, array $selectedItems): float
    {
        if ($mixAndMatchSetting->pricing_type === 'fixed_price') {
            return $mixAndMatchSetting->fixed_price;
        }

        // Per-item pricing
        $total = 0;

        $childProducts = Product::query()
            ->whereIn('id', array_keys($selectedItems))
            ->get()
            ->keyBy('id');

        foreach ($selectedItems as $childProductId => $quantity) {
            if (isset($childProducts[$childProductId])) {
                $childProduct = $childProducts[$childProductId];
                $total += $childProduct->price * $quantity;
            }
        }

        return $total;
    }

    /**
     * Get the cart item options for a Mix and Match product
     *
     * @param CartItem $cartItem
     * @return array
     */
    public function getCartItemOptions(CartItem $cartItem): array
    {
        $options = [];
        
        $mixAndMatchItems = Arr::get($cartItem->options, 'mix_and_match_items', []);
        
        if (!$mixAndMatchItems || !is_array($mixAndMatchItems)) {
            return $options;
        }

        $childProducts = Product::query()
            ->whereIn('id', array_keys($mixAndMatchItems))
            ->get()
            ->keyBy('id');

        foreach ($mixAndMatchItems as $childProductId => $quantity) {
            if (isset($childProducts[$childProductId])) {
                $childProduct = $childProducts[$childProductId];
                $options[] = [
                    'label' => $childProduct->name,
                    'value' => $quantity,
                ];
            }
        }

        return $options;
    }
}
