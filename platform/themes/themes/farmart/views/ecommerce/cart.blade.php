@php
    Theme::layout('full-width');
    Theme::set('pageTitle', __('Shopping Cart'));
@endphp

<div class="row cart-page-content py-5 mt-3">
    <div class="col-12">
        <form
            class="form--shopping-cart cart-form"
            method="post"
            action="{{ route('public.cart.update') }}"
        >
            @csrf
            @if (count($products) > 0)
                <table
                    class="table cart-form__contents"
                    cellspacing="0"
                >
                    <thead>
                        <tr>
                            <th class="product-thumbnail"></th>
                            <th class="product-name">{{ __('Product') }}</th>
                            <th class="product-price product-md d-md-table-cell d-none">{{ __('Price') }}</th>
                            <th class="product-quantity product-md d-md-table-cell d-none">{{ __('Quantity') }}</th>
                            <th class="product-subtotal product-md d-md-table-cell d-none">{{ __('Total') }}</th>
                            <th class="product-remove"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (Cart::instance('cart')->content() as $key => $cartItem)
                            @php
                                $product = $products->find($cartItem->id);
                            @endphp

                            @if (!empty($product))
                                <tr class="cart-form__cart-item cart_item">
                                    <td class="product-thumbnail">
                                        <input
                                            name="items[{{ $key }}][rowId]"
                                            type="hidden"
                                            value="{{ $cartItem->rowId }}"
                                        >

                                        <a
                                            href="{{ $product->original_product->url }}"
                                            style="max-width: 74px; display: inline-block;"
                                        >
                                            <img
                                                class="lazyload"
                                                data-src="{{ RvMedia::getImageUrl($cartItem->options->image, 'thumb', false, RvMedia::getDefaultImage()) }}"
                                                src="{{ image_placeholder(RvMedia::getImageUrl($cartItem->options->image, 'thumb', false, RvMedia::getDefaultImage())) }}"
                                                alt="{{ $product->original_product->name }}"
                                            >
                                        </a>
                                    </td>
                                    <td
                                        class="product-name d-md-table-cell d-block"
                                        data-title="{{ __('Product') }}"
                                    >
                                        <a
                                            href="{{ $product->original_product->url }}">{{ $product->original_product->name }}</a>
                                        @if (is_plugin_active('marketplace') && $product->original_product->store->id)
                                            <div class="variation-group">
                                                <span class="text-secondary">{{ __('Vendor') }}: </span>
                                                <span class="text-primary ms-1">
                                                    <a
                                                        href="{{ $product->original_product->store->url }}">{{ $product->original_product->store->name }}</a>
                                                </span>
                                            </div>
                                        @endif
                                        @if ($attributes = Arr::get($cartItem->options, 'attributes'))
                                            <p class="mb-0">
                                                <small>{{ $attributes }}</small>
                                            </p>
                                        @endif
                                        @if (EcommerceHelper::isEnabledProductOptions() && !empty($cartItem->options['options']))
                                            {!! render_product_options_html($cartItem->options['options'], $product->front_sale_price_with_taxes) !!}
                                        @endif

                                        @include(
                                            EcommerceHelper::viewPath('includes.cart-item-options-extras'),
                                            ['options' => $cartItem->options]
                                        )
                                        
                                        {{-- Mix and Match Items --}}
                                        @php
                                            // Debug information
                                            if (!empty($cartItem->options['is_mix_and_match'])) {
                                                echo '<div style="background-color: #f8f9fa; padding: 10px; margin-top: 10px; border-radius: 5px;">';
                                                echo '<strong>Mix and Match Container</strong><br>';
                                                
                                                if (!empty($cartItem->options['mix_and_match_items'])) {
                                                    echo '<ul style="margin-top: 5px; padding-left: 20px;">';
                                                    foreach ($cartItem->options['mix_and_match_items'] as $item) {
                                                        echo '<li>';
                                                        echo '<strong>' . $item['name'] . '</strong> × ' . $item['qty'];
                                                        if (!empty($item['attributes'])) {
                                                            echo ' <small>' . $item['attributes'] . '</small>';
                                                        }
                                                        echo '</li>';
                                                    }
                                                    echo '</ul>';
                                                } else {
                                                    echo '<p>No items found in mix and match container.</p>';
                                                }
                                                
                                                echo '</div>';
                                            }
                                        @endphp
                                    </td>
                                    <td
                                        class="product-price product-md d-md-table-cell d-block"
                                        data-title="Price"
                                    >
                                        <div class="box-price">
                                            <span class="d-md-none title-price">{{ __('Price') }}: </span>
                                            <span class="quantity">
                                                <span class="price-current">{{ format_price($cartItem->price) }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="product-quantity product-md d-md-table-cell d-block"
                                        data-title="{{ __('Quantity') }}"
                                    >
                                        <div class="product-button">
                                            {!! Theme::partial(
                                                'ecommerce.product-quantity',
                                                [
                                                    'name' => "items[$key][values][qty]",
                                                    'value' => $cartItem->qty,
                                                    'attributes' => ['class' => 'input-sm', 'id' => uniqid()],
                                                ]
                                            ) !!}
                                        </div>
                                    </td>
                                    <td
                                        class="product-subtotal product-md d-md-table-cell d-block"
                                        data-title="{{ __('Total') }}"
                                    >
                                        <div class="box-price">
                                            <span class="d-md-none title-price">{{ __('Total') }}: </span>
                                            <span class="fw-bold">
                                                {{ format_price($cartItem->price * $cartItem->qty) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="product-remove">
                                        <a
                                            class="fs-4 remove btn remove-cart-item"
                                            href="#"
                                            data-url="{{ route('public.cart.remove', $cartItem->rowId) }}"
                                            aria-label="{{ __('Remove this item') }}"
                                        >
                                            <span class="svg-icon">
                                                <svg>
                                                    <use
                                                        href="#svg-icon-trash"
                                                        xlink:href="#svg-icon-trash"
                                                    ></use>
                                                </svg>
                                            </span>
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <div class="row cart-actions">
                    <div class="col-md-6">
                        <div class="coupon">
                            @if (Cart::instance('cart')->count() > 0)
                                <input
                                    type="text"
                                    name="coupon_code"
                                    class="form-control"
                                    placeholder="{{ __('Enter coupon code...') }}"
                                >
                                <button
                                    class="btn btn-primary btn-apply-coupon-code"
                                    type="button"
                                    data-url="{{ route('public.coupon.apply') }}"
                                >{{ __('Apply') }}</button>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="actions">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                name="checkout"
                            >{{ __('Update cart') }}</button>
                            <a
                                class="btn btn-secondary"
                                href="{{ route('public.products') }}"
                            >{{ __('Continue Shopping') }}</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 col-md-8"></div>
                    <div class="col-6 col-md-4">
                        <div class="cart_totals bg-light p-4 rounded">
                            <h5 class="mb-3">{{ __('Cart totals') }}</h5>
                            <div class="cart-subtotal d-flex justify-content-between border-bottom pb-3 mb-3">
                                <span class="title fw-bold">{{ __('Subtotal') }}:</span>
                                <span class="amount fw-bold">
                                    <span class="price-current">{{ format_price(Cart::instance('cart')->rawSubTotal()) }}</span>
                                </span>
                            </div>
                            @if (EcommerceHelper::isTaxEnabled())
                                <div class="cart-subtotal d-flex justify-content-between border-bottom pb-3 mb-3">
                                    <span class="title fw-bold">{{ __('Tax') }}:</span>
                                    <span class="amount fw-bold">
                                        <span class="price-current">{{ format_price(Cart::instance('cart')->rawTax()) }}</span>
                                    </span>
                                </div>
                            @endif
                            @if ($couponDiscountAmount > 0 && session('applied_coupon_code'))
                                <div class="cart-subtotal d-flex justify-content-between border-bottom pb-3 mb-3">
                                    <span class="title">
                                        <span
                                            class="fw-bold">{{ __('Coupon code: :code', ['code' => session('applied_coupon_code')]) }}</span>
                                        (<small>
                                            <a
                                                class="btn-remove-coupon-code text-danger"
                                                data-url="{{ route('public.coupon.remove') }}"
                                                data-processing-text="{{ __('Removing...') }}"
                                                href="#"
                                            >{{ __('Remove') }}</a>
                                        </small>)
                                    </span>

                                    <span class="amount fw-bold">{{ format_price($couponDiscountAmount) }}</span>
                                </div>
                            @endif
                            @if ($promotionDiscountAmount)
                                <div class="cart-subtotal d-flex justify-content-between border-bottom pb-3 mb-3">
                                    <span class="title">
                                        <span class="fw-bold">{{ __('Discount promotion') }}:</span>
                                    </span>

                                    <span
                                        class="amount fw-bold">{{ format_price($promotionDiscountAmount) }}</span>
                                </div>
                            @endif
                            <div class="order-total d-flex justify-content-between pb-3 mb-3">
                                <span class="title">
                                    <h6 class="mb-0">{{ __('Total') }}</h6>
                                    <small>{{ __('(Shipping fees not included)') }}</small>
                                </span>
                                <span class="amount fw-bold fs-6 text-green">
                                    <span
                                        class="price-current">{{ $promotionDiscountAmount + $couponDiscountAmount > Cart::instance('cart')->rawTotal() ? format_price(0) : format_price(Cart::instance('cart')->rawTotal() - $promotionDiscountAmount - $couponDiscountAmount) }}</span>
                                </span>
                            </div>
                            @if (session('tracked_start_checkout'))
                                <div class="proceed-to-checkout">
                                    <div class="d-grid gap-2">
                                        <a
                                            class="checkout-button btn btn-primary"
                                            href="{{ route('public.checkout.information', session('tracked_start_checkout')) }}"
                                        >{{ __('Proceed to checkout') }}</a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center my-5">
                    <span class="svg-icon svg-icon-lg">
                        <svg>
                            <use
                                href="#svg-icon-cart"
                                xlink:href="#svg-icon-cart"
                            ></use>
                        </svg>
                    </span>
                    <h3 class="mt-4">{{ __('Your cart is empty!') }}</h3>
                    <div class="mt-3">
                        <p>{{ __('Looks like you haven\'t added any items to the cart yet.') }}</p>
                        <div class="mt-3">
                            <a
                                href="{{ route('public.products') }}"
                                class="btn btn-primary"
                            >{{ __('Shop Now') }}</a>
                        </div>
                    </div>
                </div>
            @endif
        </form>

        @if ($crossSellProducts->isNotEmpty())
            <div class="row align-items-center mb-2 widget-header">
                <h2 class="col-auto mb-0 py-2">{{ __('Customers who bought this item also bought') }}</h2>
            </div>
            <div class="row row-cols-lg-6 row-cols-md-4 row-cols-3 g-0 products-with-border">
                @foreach ($crossSellProducts as $crossSellProduct)
                    <div class="col">
                        <div class="product-inner">
                            {!! Theme::partial('ecommerce.product-item', ['product' => $crossSellProduct]) !!}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
