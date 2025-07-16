@if (!empty($productOptions['is_mix_and_match']) && !empty($productOptions['mix_and_match_items']))
    {{-- Mix and Match items will be displayed in cart-item-options-extras.blade.php --}}
@else
    {{-- Default product options rendering --}}
    @if ($displayBasePrice && $basePrice != null)
        <div class="small d-flex justify-content-between">
            <span>{{ trans('plugins/ecommerce::product-option.price') }}: <strong>{{ format_price($basePrice) }}</strong></span>
        </div>
    @endif

    @if (!empty($productOptions['optionCartValue']))
        @foreach ($productOptions['optionCartValue'] as $key => $optionValue)
            @php
                $price = 0;
                $totalOptionValue = count($optionValue);
            @endphp
            @continue(!$totalOptionValue)
            <div class="small d-flex justify-content-between">
                <span>
                    {{ $productOptions['optionInfo'][$key] }}:
                    @foreach ($optionValue as $value)
                        @php
                            if ($value['affect_price']) {
                                if ($value['affect_type'] == 1) {
                                    $price += ($basePrice * $value['affect_price']) / 100;
                                } else {
                                    $price += $value['affect_price'];
                                }
                            }
                        @endphp
                        <strong>{{ $value['option_value'] }}</strong>
                        @if ($key + 1 < $totalOptionValue)
                            ,
                        @endif
                    @endforeach
                </span>
                @if ($price > 0)
                    <strong>+ {{ format_price($price) }}</strong>
                @endif
            </div>
        @endforeach
    @endif
@endif
