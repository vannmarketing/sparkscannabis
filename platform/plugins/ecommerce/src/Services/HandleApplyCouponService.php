<?php

namespace Botble\Ecommerce\Services;

use Botble\Ecommerce\Enums\DiscountTargetEnum;
use Botble\Ecommerce\Enums\DiscountTypeEnum;
use Botble\Ecommerce\Enums\DiscountTypeOptionEnum;
use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Models\Discount;
use Botble\Ecommerce\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HandleApplyCouponService
{
    public function execute(string $coupon, array $sessionData = [], array $cartData = [], ?string $prefix = ''): array
    {
        $token = OrderHelper::getOrderSessionToken();

        if (! $token) {
            $token = OrderHelper::getOrderSessionToken();
        }

        if (! $sessionData) {
            $sessionData = OrderHelper::getOrderSessionData($token);
        }

        $rawTotal = Arr::get($cartData, 'rawTotal', Cart::instance('cart')->rawTotal());

        $sessionData['raw_total'] = $rawTotal;

        $couponCode = trim($coupon);

        $discount = $this->getCouponData($couponCode, $sessionData);

        if (! $discount) {
            return [
                'error' => true,
                'message' => trans('plugins/ecommerce::discount.invalid_coupon'),
            ];
        }

        $customerId = auth('customer')->check() ? auth('customer')->id() : 0;

        $resultCondition = $this->checkConditionDiscount($discount, $sessionData, $customerId);
        if (Arr::get($resultCondition, 'error')) {
            return $resultCondition;
        }

        $couponDiscountAmount = 0;
        $isFreeShipping = false;
        $discountTypeOption = null;
        $validCartItemIds = [];

        if ($discount->type_option == DiscountTypeOptionEnum::SHIPPING) {
            $isFreeShipping = true;
        } else {
            $discountTypeOption = $discount->type_option;
            $couponData = $this->getCouponDiscountAmount($discount, $cartData, $sessionData);

            $couponDiscountAmount = Arr::get($couponData, 'discount_amount', 0);
            $validCartItemIds = Arr::get($couponData, 'valid_cart_item_ids', 0);
        }

        if ($couponDiscountAmount < 0) {
            $couponDiscountAmount = 0;
        }

        if ($isFreeShipping) {
            if ($prefix) {
                Arr::set($sessionData, $prefix . 'is_free_shipping', true);
            } else {
                Arr::set($sessionData, 'is_free_shipping', true);
            }
        }

        if ($prefix) {
            switch ($discountTypeOption) {
                case DiscountTypeOptionEnum::PERCENTAGE:
                case DiscountTypeOptionEnum::SAME_PRICE:
                    Arr::set($sessionData, $prefix . 'coupon_discount_amount', $couponDiscountAmount);

                    break;
                default:
                    Arr::set($sessionData, $prefix . 'coupon_discount_amount', 0);

                    break;
            }
        } else {
            Arr::set($sessionData, 'coupon_discount_amount', $couponDiscountAmount);
        }

        OrderHelper::setOrderSessionData($token, $sessionData);

        session()->put('applied_coupon_code', $couponCode);
        session()->forget('auto_apply_coupon_code');

        return [
            'error' => false,
            'data' => [
                'discount_amount' => $couponDiscountAmount,
                'is_free_shipping' => $isFreeShipping,
                'discount_type_option' => $discount->type_option,
                'discount' => $discount,
                'valid_cart_item_ids' => $validCartItemIds,
            ],
        ];
    }

    public function getCouponData(string $couponCode, array $sessionData = []): Discount|Model|null
    {
        $couponCode = trim($couponCode);

        // @phpstan-ignore-next-line
        $discount = Discount::query()
            ->where('code', $couponCode)
            ->where('type', DiscountTypeEnum::COUPON)
            ->where(function (Builder $query) {
                return $query
                    ->whereNull('start_date')
                    ->orWhere('start_date', '<=', Carbon::now()->toDateTimeString());
            })
            ->where(function (Builder $query) {
                return $query
                    ->whereNull('end_date')
                    ->orWhere('end_date', '>=', Carbon::now()->toDateTimeString());
            })
            ->where(function (Builder $query) {
                return $query
                    ->whereNull('quantity')
                    ->orWhereColumn('total_used', '<', 'quantity');
            })
            ->first();

        if ($discount) {
            // Use the model's method to check if the discount is active for the current day
            if (! $discount->isActiveForCurrentDay()) {
                return null; // Coupon is not active today
            }

            $discount->loadMissing(['products', 'productCollections', 'productCategories', 'customers']);
        }

        return $discount;
    }

    public function applyCouponWhenCreatingOrderFromAdmin(Request $request, array $cartData = []): array
    {
        $couponCode = trim($request->input('coupon_code'));
        $rawTotal = Arr::get($cartData, 'rawTotal', $request->input('sub_amount'));

        $sessionData = [
            'shipping_amount' => $request->input('shipping_amount'),
            'state' => $request->input('state'),
            'raw_total' => $rawTotal,
            'promotion_discount_amount' => Arr::get($cartData, 'promotion_discount_amount', $request->input('promotion_amount')),
        ];

        $discount = $this->getCouponData($couponCode, $sessionData);

        if (! $discount) {
            return [
                'error' => true,
                'message' => trans('plugins/ecommerce::discount.invalid_coupon'),
            ];
        }

        $customerId = $request->input('customer_id');
        $resultCondition = $this->checkConditionDiscount($discount, $sessionData, $customerId);
        if (Arr::get($resultCondition, 'error')) {
            return $resultCondition;
        }

        $couponDiscountAmount = 0;
        $isFreeShipping = false;

        if ($discount->type_option == DiscountTypeOptionEnum::SHIPPING) {
            $isFreeShipping = true;
        } else {
            $couponData = $this->getCouponDiscountAmount($discount, $cartData);
            $couponDiscountAmount = Arr::get($couponData, 'discount_amount', 0);
        }

        if ($couponDiscountAmount < 0) {
            $couponDiscountAmount = 0;
        }

        return [
            'error' => false,
            'data' => [
                'discount_amount' => $couponDiscountAmount,
                'is_free_shipping' => $isFreeShipping,
                'discount' => $discount,
            ],
        ];
    }

    public function checkConditionDiscount(Discount|Model $discount, array $sessionData = [], ?int $customerId = 0): array
    {
        if (! $discount->can_use_with_flash_sale) {
            /** @var Collection<Product> $products */
            $products = Cart::instance('cart')->products();
            $productsInFlashSales = [];

            /** @var Product $product */
            foreach ($products as $product) {
                if ($product->getFlashSalePrice() >= $product->price) {
                    continue;
                }

                $productsInFlashSales[] = $product->original_product->name;
            }

            if (! empty($productsInFlashSales)) {
                return [
                    'error' => true,
                    'message' => trans('plugins/ecommerce::discount.cannot_use_same_time_with_flash_sale', [
                        'product_name' => '<strong>' . implode(', ', $productsInFlashSales) . '</strong>',
                    ]),
                ];
            }
        }

        /**
         * @var Discount $discount
         */
        if ($discount->target == DiscountTargetEnum::CUSTOMER) {
            $discountCustomers = $discount->customers->pluck('id')->all();
            if (! $customerId || ! in_array($customerId, $discountCustomers)) {
                return [
                    'error' => true,
                    'message' => trans('plugins/ecommerce::discount.invalid_coupon'),
                ];
            }
        }

        if ($discount->target == DiscountTargetEnum::ONCE_PER_CUSTOMER) {
            if (! $customerId) {
                return [
                    'error' => true,
                    'message' => trans('plugins/ecommerce::discount.you_need_login_to_use_coupon_code'),
                ];
            } elseif ($discount->usedByCustomers()->where('customer_id', auth('customer')->id())->exists()) {
                return [
                    'error' => true,
                    'message' => trans('plugins/ecommerce::discount.you_used_coupon_code'),
                ];
            }
        }

        if (! $discount->can_use_with_promotion && (float) Arr::get($sessionData, 'promotion_discount_amount')) {
            return [
                'error' => true,
                'message' => trans('plugins/ecommerce::discount.cannot_use_same_time_with_other_discount_program'),
            ];
        }

        $rawTotal = (float) Arr::get($sessionData, 'raw_total');

        if (
            in_array($discount->type_option, [DiscountTypeOptionEnum::AMOUNT, DiscountTypeOptionEnum::PERCENTAGE])
            && $discount->target == DiscountTargetEnum::MINIMUM_ORDER_AMOUNT && $discount->min_order_price > $rawTotal
        ) {
            return [
                'error' => true,
                'message' => trans('plugins/ecommerce::discount.minimum_order_amount_error', [
                    'minimum_amount' => format_price($discount->min_order_price),
                    'add_more' => format_price($rawTotal - $discount->min_order_price),
                ]),
            ];
        }

        return [
            'error' => false,
        ];
    }

    protected function getCouponDiscountAmount(Discount|Model $discount, array $cartData = [], array $sessionData = []): array
    {
        /**
         * @var Discount $discount
         */

        $couponDiscountAmount = 0;
        $discountValue = $discount->value;
        $validCartItems = collect();

        $cartItems = Arr::get($cartData, 'cartItems', Cart::instance('cart')->content());
        $countCart = Arr::get($cartData, 'countCart', Cart::instance('cart')->count());
        $rawTotal = Arr::get($cartData, 'rawTotal', Cart::instance('cart')->rawTotal());
        $cartAmount = Arr::get($cartData, 'cartAmount', Cart::instance('cart')->rawSubTotal());

        if (! $discount->target || in_array($discount->target, [DiscountTargetEnum::ALL_ORDERS, DiscountTargetEnum::ONCE_PER_CUSTOMER])) {
            $validCartItems = $cartItems;
        } elseif ($discount->target === DiscountTargetEnum::AMOUNT_MINIMUM_ORDER) {
            $validCartItems = $cartItems;
        } elseif ($discount->target === DiscountTargetEnum::NON_SALE_ITEMS) {
            $products = Product::query()
                ->whereIn('id', $cartItems->pluck('id')->all())
                ->get();

            $validCartItems = $cartItems->filter(function ($cartItem) use ($products) {
                $product = $products->firstWhere('id', $cartItem->id);
                if (!$product) {
                    return false;
                }
                
                // Check if the product has a sale price
                return !$product->sale_price || $product->sale_price == $product->price;
            });
        }

        $productIds = [];
        $productCollections = [];

        switch ($discount->type_option) {
            case DiscountTypeOptionEnum::AMOUNT:
                switch ($discount->target) {
                    case DiscountTargetEnum::MINIMUM_ORDER_AMOUNT:
                    case DiscountTargetEnum::ONCE_PER_CUSTOMER:
                    case DiscountTargetEnum::ALL_ORDERS:
                        $couponDiscountAmount += min($discountValue, $rawTotal);

                        break;
                    case DiscountTargetEnum::SPECIFIC_PRODUCT:
                        $discountProductIds = $discount->products->pluck('id')->all();

                        $products->loadMissing(['variationInfo', 'variationInfo.configurableProduct']);
                        $validCartItems = $cartItems->filter(function ($cartItem) use ($products, $discountProductIds) {
                            $product = $products->filter(function ($item) use ($cartItem) {
                                return $item->id == $cartItem->id || $item->original_product->id == $cartItem->id;
                            })->first();
                            if (! $product) {
                                return false;
                            }

                            if (in_array($product->original_product->id, $discountProductIds)) {
                                return true;
                            }

                            return false;
                        });

                        if ($discount->discount_on === 'per-order') {
                            $validRawTotal = Cart::instance('cart')->rawTotalByItems($validCartItems);
                            $couponDiscountAmount += min($discountValue, $validRawTotal);
                        } elseif ($discount->discount_on === 'per-every-item') {
                            foreach ($validCartItems as $cartItem) {
                                $couponDiscountAmount += min($discountValue * $cartItem->qty, $cartItem->total);
                            }
                        }

                        break;
                    case DiscountTargetEnum::PRODUCT_VARIANT:
                        $discountProductIds = $discount->products->pluck('id')->all();

                        $validCartItems = $cartItems->filter(function ($cartItem) use ($discountProductIds) {
                            if (in_array($cartItem->id, $discountProductIds)) {
                                return true;
                            }

                            return false;
                        });

                        if ($discount->discount_on === 'per-order') {
                            $validRawTotal = Cart::instance('cart')->rawTotalByItems($validCartItems);
                            $couponDiscountAmount += min($discountValue, $validRawTotal);
                        } elseif ($discount->discount_on === 'per-every-item') {
                            foreach ($validCartItems as $cartItem) {
                                $couponDiscountAmount += min($discountValue * $cartItem->qty, $cartItem->total);
                            }
                        }

                        break;
                    case DiscountTargetEnum::PRODUCT_COLLECTIONS:
                        $products->loadMissing([
                            'variationInfo',
                            'productCollections',
                            'variationInfo.configurableProduct',
                            'variationInfo.configurableProduct.productCollections',
                        ]);

                        $discountProductCollections = $discount
                            ->productCollections()
                            ->pluck('ec_product_collections.id')
                            ->all();

                        $validCartItems = $cartItems->filter(function ($cartItem) use ($products, $discountProductCollections) {
                            $product = $products->filter(function ($item) use ($cartItem) {
                                return $item->id == $cartItem->id || $item->original_product->id == $cartItem->id;
                            })->first();
                            if (! $product) {
                                return false;
                            }

                            $productCollections = $product->original_product->productCollections->pluck('id')->all();

                            if (! empty(array_intersect($productCollections, $discountProductCollections))) {
                                return true;
                            }

                            return false;
                        });

                        if ($discount->discount_on === 'per-order') {
                            $validRawTotal = Cart::instance('cart')->rawTotalByItems($validCartItems);
                            $couponDiscountAmount += min($discountValue, $validRawTotal);
                        } elseif ($discount->discount_on === 'per-every-item') {
                            foreach ($validCartItems as $cartItem) {
                                $couponDiscountAmount += min($discountValue * $cartItem->qty, $cartItem->total);
                            }
                        }

                        break;
                    case DiscountTargetEnum::PRODUCT_CATEGORIES:
                        $products->loadMissing([
                            'variationInfo',
                            'categories',
                            'variationInfo.configurableProduct',
                            'variationInfo.configurableProduct.categories',
                        ]);

                        $discountProductCategories = $discount
                            ->productCategories()
                            ->pluck('ec_product_categories.id')
                            ->all();

                        $validCartItems = $cartItems->filter(function ($cartItem) use ($products, $discountProductCategories) {
                            /**
                             * @var Product $product
                             */
                            $product = $products->filter(function ($item) use ($cartItem) {
                                return $item->id == $cartItem->id || $item->original_product->id == $cartItem->id;
                            })->first();
                            if (! $product) {
                                return false;
                            }

                            $productCategories = $product->original_product->categories->pluck('id')->all();

                            if (! empty(array_intersect($productCategories, $discountProductCategories))) {
                                return true;
                            }

                            return false;
                        });

                        if ($discount->discount_on === 'per-order') {
                            $validRawTotal = Cart::instance('cart')->rawTotalByItems($validCartItems);
                            $couponDiscountAmount += min($discountValue, $validRawTotal);
                        } elseif ($discount->discount_on === 'per-every-item') {
                            foreach ($validCartItems as $cartItem) {
                                $couponDiscountAmount += min($discountValue * $cartItem->qty, $cartItem->total);
                            }
                        }

                        break;
                    case DiscountTargetEnum::NON_SALE_ITEMS:
                        $validRawTotal = Cart::instance('cart')->rawTotalByItems($validCartItems);
                        $couponDiscountAmount += min($discountValue, $validRawTotal);
                        break;

                    default:
                        if ($countCart >= $discount->product_quantity) {
                            $couponDiscountAmount += min($discountValue, $rawTotal);
                        }

                        break;
                }

                break;
            case DiscountTypeOptionEnum::PERCENTAGE:
                switch ($discount->target) {
                    case DiscountTargetEnum::MINIMUM_ORDER_AMOUNT:
                        $couponDiscountAmount = ($rawTotal - (float) Arr::get($sessionData, 'shipping_amount', 0)) * $discountValue / 100;

                        break;
                    case DiscountTargetEnum::ONCE_PER_CUSTOMER:
                    case DiscountTargetEnum::ALL_ORDERS:
                        $couponDiscountAmount = $rawTotal * $discountValue / 100;

                        break;
                    case DiscountTargetEnum::SPECIFIC_PRODUCT:
                        $discountProductIds = $discount->products->pluck('id')->all();
                        $products->loadMissing(['variationInfo', 'variationInfo.configurableProduct']);

                        $validCartItems = $cartItems->filter(function ($cartItem) use ($products, $discountProductIds) {
                            $product = $products->filter(function ($item) use ($cartItem) {
                                return $item->id == $cartItem->id || $item->original_product->id == $cartItem->id;
                            })->first();

                            if (! $product) {
                                return false;
                            }

                            if (in_array($product->original_product->id, $discountProductIds)) {
                                return true;
                            }

                            return false;
                        });

                        foreach ($validCartItems as $cartItem) {
                            $couponDiscountAmount += $cartItem->total * $discountValue / 100;
                        }

                        break;
                    case DiscountTargetEnum::PRODUCT_VARIANT:
                        $discountProductIds = $discount->products->pluck('id')->all();

                        $validCartItems = $cartItems->filter(function ($cartItem) use ($discountProductIds) {
                            if (in_array($cartItem->id, $discountProductIds)) {
                                return true;
                            }

                            return false;
                        });
                        foreach ($validCartItems as $cartItem) {
                            $couponDiscountAmount += $cartItem->total * $discountValue / 100;
                        }

                        break;
                    case DiscountTargetEnum::PRODUCT_COLLECTIONS:
                        $products->loadMissing([
                            'variationInfo',
                            'productCollections',
                            'variationInfo.configurableProduct',
                            'variationInfo.configurableProduct.productCollections',
                        ]);

                        $discountProductCollections = $discount
                            ->productCollections()
                            ->pluck('ec_product_collections.id')
                            ->all();

                        $validCartItems = $cartItems->filter(function ($cartItem) use ($products, $discountProductCollections) {
                            $product = $products->filter(function ($item) use ($cartItem) {
                                return $item->id == $cartItem->id || $item->original_product->id == $cartItem->id;
                            })->first();
                            if (! $product) {
                                return false;
                            }

                            $productCollections = $product->original_product->productCollections->pluck('id')->all();

                            if (! empty(array_intersect($productCollections, $discountProductCollections))) {
                                return true;
                            }

                            return false;
                        });
                        foreach ($validCartItems as $cartItem) {
                            $couponDiscountAmount += $cartItem->total * $discountValue / 100;
                        }

                        break;
                    case DiscountTargetEnum::PRODUCT_CATEGORIES:
                        $products->loadMissing([
                            'variationInfo',
                            'categories',
                            'variationInfo.configurableProduct',
                            'variationInfo.configurableProduct.categories',
                        ]);

                        $discountProductCategories = $discount
                            ->productCategories()
                            ->pluck('ec_product_categories.id')
                            ->all();

                        $validCartItems = $cartItems->filter(function ($cartItem) use ($products, $discountProductCategories) {
                            /**
                             * @var Product $product
                             */
                            $product = $products->filter(function ($item) use ($cartItem) {
                                return $item->id == $cartItem->id || $item->original_product->id == $cartItem->id;
                            })->first();
                            if (! $product) {
                                return false;
                            }

                            $productCategories = $product->original_product->categories->pluck('id')->all();

                            if (! empty(array_intersect($productCategories, $discountProductCategories))) {
                                return true;
                            }

                            return false;
                        });

                        foreach ($validCartItems as $cartItem) {
                            $couponDiscountAmount += $cartItem->total * $discountValue / 100;
                        }

                        break;
                    case DiscountTargetEnum::NON_SALE_ITEMS:
                        $validRawTotal = Cart::instance('cart')->rawTotalByItems($validCartItems);
                        // Calculate percentage discount on the total of valid items
                        $couponDiscountAmount = $validRawTotal * $discountValue / 100;
                        break;

                    default:
                        if ($countCart >= $discount->product_quantity) {
                            $couponDiscountAmount = $rawTotal * $discountValue / 100;
                        }

                        break;
                }

                break;
            case DiscountTypeOptionEnum::SAME_PRICE:
                if (in_array($discount->target, [DiscountTargetEnum::SPECIFIC_PRODUCT, DiscountTargetEnum::PRODUCT_VARIANT])) {
                    foreach ($cartItems as $cartItem) {
                        if (in_array($cartItem->id, $discount->products->pluck('id')->all())) {
                            $couponDiscountAmount = max($cartItem->priceTax - $discountValue, 0) * $cartItem->qty;
                        }
                    }
                } elseif ($discount->target == DiscountTargetEnum::PRODUCT_COLLECTIONS) {
                    $products->loadMissing([
                        'variationInfo',
                        'productCollections',
                        'variationInfo.configurableProduct',
                        'variationInfo.configurableProduct.productCollections',
                    ]);

                    $discountProductCollections = $discount
                        ->productCollections()
                        ->pluck('ec_product_collections.id')
                        ->all();

                    $validCartItems = $cartItems->filter(function ($cartItem) use ($products, $discountProductCollections) {
                        $product = $products->filter(function ($item) use ($cartItem) {
                            return $item->id == $cartItem->id || $item->original_product->id == $cartItem->id;
                        })->first();

                        if (! $product) {
                            return false;
                        }

                        $productCollections = $product->original_product->productCollections->pluck('id')->all();

                        if (! empty(array_intersect($productCollections, $discountProductCollections))) {
                            return true;
                        }

                        return false;
                    });

                    foreach ($validCartItems as $cartItem) {
                        $couponDiscountAmount += max($cartItem->total - $discountValue, 0) * $cartItem->qty;
                    }
                } elseif ($discount->target == DiscountTargetEnum::PRODUCT_CATEGORIES) {
                    $products->loadMissing([
                        'variationInfo',
                        'categories',
                        'variationInfo.configurableProduct',
                        'variationInfo.configurableProduct.categories',
                    ]);

                    $discountProductCategories = $discount
                        ->productCategories()
                        ->pluck('ec_product_categories.id')
                        ->all();

                    $validCartItems = $cartItems->filter(function ($cartItem) use ($products, $discountProductCategories) {
                        /**
                         * @var Product $product
                         */
                        $product = $products->filter(function ($item) use ($cartItem) {
                            return $item->id == $cartItem->id || $item->original_product->id == $cartItem->id;
                        })->first();
                        if (! $product) {
                            return false;
                        }

                        $productCategories = $product->original_product->categories->pluck('id')->all();

                        if (! empty(array_intersect($productCategories, $discountProductCategories))) {
                            return true;
                        }

                        return false;
                    });

                    foreach ($validCartItems as $cartItem) {
                        $couponDiscountAmount += max($cartItem->total - $discountValue, 0) * $cartItem->qty;
                    }
                }

                break;
        }

        return [
            'discount_amount' => $couponDiscountAmount,
            'valid_cart_item_ids' => $validCartItems->pluck('id'),
        ];
    }
}
