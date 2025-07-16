<div class="mix-and-match-container">
    <h3>{{ trans('plugins/mix-and-match::mix-and-match.select_your_items') }}</h3>
    
    @php
        $minItems = $product->mixAndMatchSetting->min_container_size;
        $maxItems = $product->mixAndMatchSetting->max_container_size;
        $selectionText = $maxItems 
            ? trans('plugins/mix-and-match::mix-and-match.select_between', ['min' => $minItems, 'max' => $maxItems])
            : trans('plugins/mix-and-match::mix-and-match.select_between', ['min' => $minItems, 'max' => '∞']);
    @endphp
    
    <p>{{ $selectionText }}</p>
    
    <div class="row mix-and-match-items">
        @foreach($product->mixAndMatchItems as $item)
            @php
                $childProduct = $item->childProduct;
                if (!$childProduct || $childProduct->status != 'published') {
                    continue;
                }
            @endphp
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="product-item" data-id="{{ $childProduct->id }}" data-price="{{ $childProduct->price }}" data-min="{{ $item->min_qty }}" data-max="{{ $item->max_qty }}">
                    <div class="product-image">
                        <img src="{{ RvMedia::getImageUrl($childProduct->image, 'thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $childProduct->name }}">
                    </div>
                    <div class="product-details">
                        <div class="product-name">{{ $childProduct->name }}</div>
                        <div class="product-price">{{ format_price($childProduct->price) }}</div>
                        <div class="quantity-selector">
                            <button type="button" class="decrease-qty" @if($item->min_qty > 0) disabled @endif>-</button>
                            <input type="number" name="mix_and_match[{{ $childProduct->id }}]" value="{{ $item->min_qty }}" min="{{ $item->min_qty }}" max="{{ $item->max_qty }}" class="product-qty" readonly>
                            <button type="button" class="increase-qty">+</button>
                        </div>
                        @if($item->min_qty > 0)
                            <div class="required-item-notice mt-2">
                                <small class="text-danger">{{ trans('plugins/mix-and-match::mix-and-match.min_qty') }}: {{ $item->min_qty }}</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    <div class="summary">
        <div class="summary-title">{{ trans('plugins/mix-and-match::mix-and-match.selected_items') }} <span id="selected-count">0</span></div>
        <div class="summary-items" id="selected-items-list"></div>
        <div class="validation-message" id="validation-message"></div>
        
        <form id="mix-and-match-form" method="post" action="{{ route('public.cart.add-to-cart') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $product->id }}">
            <input type="hidden" name="is_mix_and_match" value="1">
            <div id="selected-items-inputs"></div>
            <button type="submit" class="btn btn-primary add-to-cart-btn" id="add-to-cart-button" @if($minItems > 0) disabled @endif>
                {{ trans('plugins/mix-and-match::mix-and-match.add_to_cart') }}
            </button>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        const $container = $('.mix-and-match-container');
        const $form = $('#mix-and-match-form');
        const $selectedCount = $('#selected-count');
        const $selectedItemsList = $('#selected-items-list');
        const $selectedItemsInputs = $('#selected-items-inputs');
        const $validationMessage = $('#validation-message');
        const $addToCartButton = $('#add-to-cart-button');
        
        const minItems = {{ $minItems }};
        const maxItems = {{ $maxItems ?: 'Infinity' }};
        const pricingType = '{{ $product->mixAndMatchSetting->pricing_type }}';
        const fixedPrice = {{ $product->mixAndMatchSetting->fixed_price ?: 0 }};
        
        // Initialize with required items
        updateSummary();
        
        // Increase quantity
        $container.on('click', '.increase-qty', function() {
            const $item = $(this).closest('.product-item');
            const $input = $item.find('.product-qty');
            const max = parseInt($item.data('max'));
            let value = parseInt($input.val());
            
            if (value < max) {
                $input.val(++value);
                $item.find('.decrease-qty').prop('disabled', false);
                if (value === max) {
                    $(this).prop('disabled', true);
                }
                updateSummary();
            }
        });
        
        // Decrease quantity
        $container.on('click', '.decrease-qty', function() {
            const $item = $(this).closest('.product-item');
            const $input = $item.find('.product-qty');
            const min = parseInt($item.data('min'));
            let value = parseInt($input.val());
            
            if (value > min) {
                $input.val(--value);
                $item.find('.increase-qty').prop('disabled', false);
                if (value === min) {
                    $(this).prop('disabled', true);
                }
                updateSummary();
            }
        });
        
        // Update summary when form is submitted
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const totalSelected = getTotalSelected();
            
            if (totalSelected < minItems) {
                $validationMessage.text(minItems === 1 
                    ? '{{ trans('plugins/mix-and-match::mix-and-match.validation.quantity_required') }}'
                    : '{{ trans('plugins/mix-and-match::mix-and-match.validation.min_items_required', ['min' => '']) }}'.replace(':min', minItems)
                );
                return;
            }
            
            if (maxItems !== Infinity && totalSelected > maxItems) {
                $validationMessage.text('{{ trans('plugins/mix-and-match::mix-and-match.validation.max_items_allowed', ['max' => '']) }}'.replace(':max', maxItems));
                return;
            }
            
            $validationMessage.text('');
            
            // Submit the form via AJAX
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function(res) {
                    if (res.error) {
                        $validationMessage.text(res.message);
                    } else {
                        window.showSuccess(res.message);
                        
                        // Update cart count in the header
                        if (typeof window.setCartCount === 'function') {
                            window.setCartCount(res.data.count);
                        }
                    }
                },
                error: function(res) {
                    $validationMessage.text(res.responseJSON.message || 'Error adding to cart');
                }
            });
        });
        
        function updateSummary() {
            const selectedItems = [];
            let totalSelected = 0;
            let totalPrice = 0;
            
            $selectedItemsInputs.empty();
            
            $('.product-item').each(function() {
                const $item = $(this);
                const id = $item.data('id');
                const qty = parseInt($item.find('.product-qty').val());
                
                if (qty > 0) {
                    const name = $item.find('.product-name').text();
                    const price = parseFloat($item.data('price'));
                    
                    selectedItems.push({
                        id: id,
                        name: name,
                        qty: qty,
                        price: price
                    });
                    
                    totalSelected += qty;
                    totalPrice += price * qty;
                    
                    // Add hidden input for form submission
                    $selectedItemsInputs.append(`<input type="hidden" name="mix_and_match_items[${id}]" value="${qty}">`);
                }
            });
            
            // Update selected count
            $selectedCount.text(totalSelected);
            
            // Update selected items list
            $selectedItemsList.empty();
            if (selectedItems.length > 0) {
                selectedItems.forEach(item => {
                    $selectedItemsList.append(`
                        <div class="summary-item">
                            <span>${item.name} × ${item.qty}</span>
                            <span>${pricingType === 'per_item' ? formatPrice(item.price * item.qty) : ''}</span>
                        </div>
                    `);
                });
                
                // Add total price
                if (pricingType === 'per_item') {
                    $selectedItemsList.append(`
                        <div class="summary-item font-weight-bold mt-2">
                            <span>Total:</span>
                            <span>${formatPrice(totalPrice)}</span>
                        </div>
                    `);
                } else {
                    $selectedItemsList.append(`
                        <div class="summary-item font-weight-bold mt-2">
                            <span>Fixed Price:</span>
                            <span>${formatPrice(fixedPrice)}</span>
                        </div>
                    `);
                }
            } else {
                $selectedItemsList.append(`<div class="summary-item">No items selected</div>`);
            }
            
            // Enable/disable add to cart button
            if (totalSelected >= minItems && (maxItems === Infinity || totalSelected <= maxItems)) {
                $addToCartButton.prop('disabled', false);
                $validationMessage.text('');
            } else {
                $addToCartButton.prop('disabled', true);
                
                if (totalSelected < minItems) {
                    $validationMessage.text(minItems === 1 
                        ? '{{ trans('plugins/mix-and-match::mix-and-match.validation.quantity_required') }}'
                        : '{{ trans('plugins/mix-and-match::mix-and-match.validation.min_items_required', ['min' => '']) }}'.replace(':min', minItems)
                    );
                } else if (maxItems !== Infinity && totalSelected > maxItems) {
                    $validationMessage.text('{{ trans('plugins/mix-and-match::mix-and-match.validation.max_items_allowed', ['max' => '']) }}'.replace(':max', maxItems));
                }
            }
        }
        
        function getTotalSelected() {
            let total = 0;
            $('.product-item').each(function() {
                total += parseInt($(this).find('.product-qty').val());
            });
            return total;
        }
        
        function formatPrice(price) {
            return price.toLocaleString('en-US', {
                style: 'currency',
                currency: '{{ get_application_currency()->title }}',
                minimumFractionDigits: {{ get_application_currency()->decimals }}
            });
        }
    });
</script>
