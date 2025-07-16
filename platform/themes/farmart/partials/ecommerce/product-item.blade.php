<div class="product-thumbnail">
    <a
        class="product-loop__link img-fluid-eq"
        href="{{ $product->is_variation && isset($product->original_product) ? $product->original_product->url : $product->url }}"
        @if ($product->is_variation && isset($product->original_product))
            data-variation-id="{{ $product->id }}"
        @endif
        tabindex="0"
    >
        <div class="img-fluid-eq__dummy"></div>
        <div class="img-fluid-eq__wrap">
            <img
                class="lazyload product-thumbnail__img"
                data-src="{{ RvMedia::getImageUrl($product->image, 'small', false, RvMedia::getDefaultImage()) }}"
                src="{{ image_placeholder($product->image, 'small') }}"
                alt="{{ $product->name }}"
            >
        </div>
        <span class="ribbons">
            @if ($product->isOutOfStock())
                <span class="ribbon out-stock">{{ __('Out Of Stock') }}</span>
            @else
                @if ($product->productLabels->isNotEmpty())
                    @foreach ($product->productLabels as $label)
                        <span
                            class="ribbon"
                            {!! $label->css_styles !!}
                        >{{ $label->name }}</span>
                    @endforeach
                @else
                    @if ($product->front_sale_price !== $product->price)
                        <div
                            class="featured ribbon"
                            dir="ltr"
                        >{{ get_sale_percentage($product->price, $product->front_sale_price) }}</div>
                    @endif
                @endif
            @endif
        </span>
    </a>
    {!! Theme::partial(
        'ecommerce.product-loop-buttons',
        compact('product') + (!empty($wishlistIds) ? compact('wishlistIds') : []),
    ) !!}
</div>
@php
    $isConfigurable = $product->variations()->count() > 0;
    $isMixAndMatch = method_exists($product, 'isMixAndMatch') && $product->isMixAndMatch();
@endphp
<div class="card bb-product-item" {{ $isMixAndMatch ? 'data-mix-and-match=1' : '' }}>
    <div class="product-details position-relative">
        @if (is_plugin_active('marketplace') && $product->store->id)
            <div class="sold-by-meta">
                <a
                    href="{{ $product->store->url }}"
                    tabindex="0"
                >{{ $product->store->name }}</a>
            </div>
        @endif
        <div class="product-content-box">
            <h3 class="product__title">
                <a
                    href="{{ $product->is_variation && isset($product->original_product) ? $product->original_product->url : $product->url }}"
                    tabindex="0"
                >
                    @php
                        // Extract the base product name without attributes for cleaner display
                        $displayName = $product->name;
                        $attributeInfo = '';
                        
                        // If it's a variation, try to extract attributes
                        if ($product->is_variation) {
                            // First try to extract from name if it contains attributes in parentheses
                            if (preg_match('/(.+)\s+\((.+)\)/', $product->name, $matches)) {
                                $displayName = $matches[1];
                                // Extract just the values from attribute info (remove labels)
                                $attributeParts = explode(', ', $matches[2]);
                                $attributeValues = [];
                                foreach ($attributeParts as $part) {
                                    if (strpos($part, ':') !== false) {
                                        $parts = explode(':', $part, 2);
                                        $attributeValues[] = trim($parts[1]);
                                    } else {
                                        $attributeValues[] = trim($part);
                                    }
                                }
                                $attributeInfo = implode(', ', $attributeValues);
                            } else {
                                // If not found in name, try to get from the database
                                try {
                                    if (isset($product->variationInfo) && $product->variationInfo) {
                                        $variationItems = DB::table('ec_product_variation_items as pvi')
                                            ->join('ec_product_attributes as pa', 'pa.id', '=', 'pvi.attribute_id')
                                            ->join('ec_product_attribute_sets as pas', 'pas.id', '=', 'pa.attribute_set_id')
                                            ->where('pvi.variation_id', $product->variationInfo->id)
                                            ->select('pas.title as attribute_set', 'pa.title as attribute_value')
                                            ->get();
                                            
                                        $attributes = [];
                                        foreach ($variationItems as $item) {
                                            // Only include the attribute value, not the label
                                            $attributes[] = $item->attribute_value;
                                        }
                                        if (!empty($attributes)) {
                                            $attributeInfo = implode(', ', $attributes);
                                        }
                                    }
                                } catch (\Exception $e) {
                                    // Silently fail and continue without attributes
                                }
                            }
                        }
                    @endphp
                    {{ $displayName }}
                </a>
                @if (!empty($attributeInfo))
                    <div class="small text-muted mt-1">{{ $attributeInfo }}</div>
                @endif
            </h3>
            @if (EcommerceHelper::isReviewEnabled())
                {!! Theme::partial('star-rating', ['avg' => $product->reviews_avg, 'count' => $product->reviews_count]) !!}
            @endif
            {!! Theme::partial('ecommerce.product-price', compact('product')) !!}
            @if (!empty($isFlashSale))
                <div class="deal-sold row mt-2">
                    @if (Botble\Ecommerce\Facades\FlashSale::isShowSaleCountLeft())
                        <div class="deal-text col-auto">
                            <span class="sold fw-bold">
                                @if ($product->pivot->quantity > $product->pivot->sold)
                                    <span class="text">{{ __('Sold') }}: </span>
                                    <span class="value">{{ (int) $product->pivot->sold }} /
                                        {{ (int) $product->pivot->quantity }}</span>
                                @else
                                    <span class="text text-danger">{{ __('Sold out') }}</span>
                                @endif
                            </span>
                        </div>
                    @endif
                    <div class="deal-progress col">
                        <div class="progress">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                aria-label="{{ __('Sold out') }}"
                                aria-valuenow="{{ $product->pivot->quantity > 0 ? ($product->pivot->sold / $product->pivot->quantity) * 100 : 0 }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                style="width: {{ $product->pivot->quantity > 0 ? ($product->pivot->sold / $product->pivot->quantity) * 100 : 0 }}%"
                            >
                            </div>
                        </div>
                    </div>
                </div>
            @endisset
        </div>
        <div class="product-bottom-box">
            {!! Theme::partial('ecommerce.product-cart-form', compact('product')) !!}
        </div>
    </div>
</div>
