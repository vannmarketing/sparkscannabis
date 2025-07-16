@if ($product->isMixAndMatch() && $product->mixAndMatchItems->isNotEmpty())
    <div class="mix-and-match-container mt-4">
        <h4>{{ trans('plugins/mix-and-match::mix-and-match.select_your_items') }}</h4>
        
        @php
            $minItems = $product->mixAndMatchSetting->min_container_size;
            $maxItems = $product->mixAndMatchSetting->max_container_size;
            $selectionText = $maxItems 
                ? trans('plugins/mix-and-match::mix-and-match.select_between', ['min' => $minItems, 'max' => $maxItems])
                : trans('plugins/mix-and-match::mix-and-match.select_between', ['min' => $minItems, 'max' => '∞']);
        @endphp
        
        <p>{{ $selectionText }}</p>
        
        <div class="mix-and-match-items list-view">
            <table class="table">
                <thead>
                    <tr>
                        <th width="80">{{ __('Image') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th width="120">{{ __('Price') }}</th>
                        <th width="150">{{ __('Quantity') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product->mixAndMatchItems as $item)
                        @php
                            $childProduct = $item->childProduct;
                            if (!$childProduct || $childProduct->status != 'published') {
                                continue;
                            }
                        @endphp
                        <tr class="product-item" data-id="{{ $childProduct->id }}" data-price="{{ $childProduct->price }}" data-min="{{ $item->min_qty }}" data-max="{{ $item->max_qty }}" data-is-variation="{{ $childProduct->is_variation ? '1' : '0' }}">
                            <td>
                                <div class="product-image">
                                    <img src="{{ RvMedia::getImageUrl($childProduct->image, 'thumb', false, RvMedia::getDefaultImage()) }}" alt="{{ $childProduct->name }}" class="img-fluid" width="60">
                                </div>
                            </td>
                            <td>
                                <div class="product-name">
                                    {{ $childProduct->name }}
                                    @if($childProduct->is_variation)
                                        <span class="badge bg-info ms-1">{{ __('Variation') }}</span>
                                        @php
                                            $attributes = $childProduct->variationProductAttributes;
                                            if ($attributes && $attributes->count() > 0) {
                                                $attributeText = [];
                                                foreach ($attributes as $attribute) {
                                                    $attributeText[] = $attribute->attribute_set_title . ': ' . $attribute->title;
                                                }
                                                echo '<div class="variation-attributes"><small class="text-muted">(' . implode(', ', $attributeText) . ')</small></div>';
                                            }
                                        @endphp
                                    @endif
                                </div>
                                @if($item->min_qty > 0)
                                    <div class="required-item-notice">
                                        <small class="text-danger">{{ trans('plugins/mix-and-match::mix-and-match.min_qty') }}: {{ $item->min_qty }}</small>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="product-price">{{ format_price($childProduct->price) }}</div>
                            </td>
                            <td>
                                <div class="quantity-selector d-flex align-items-center">
                                    <button type="button" class="btn btn-sm btn-secondary decrease-qty" @if($item->min_qty > 0 && $item->min_qty == 0) disabled @endif>-</button>
                                    <input type="number" name="mix_and_match[{{ $childProduct->id }}]" value="{{ $item->min_qty }}" min="{{ $item->min_qty }}" max="{{ $item->max_qty ?: 100 }}" class="form-control form-control-sm product-qty mx-2" style="width: 70px;" 
                                        data-product-id="{{ $childProduct->id }}" 
                                        data-product-name="{{ $childProduct->name }}" 
                                        data-product-image="{{ $childProduct->image }}" 
                                        data-is-variation="{{ $childProduct->is_variation ? '1' : '0' }}" 
                                        @if($childProduct->is_variation && $childProduct->variationProductAttributes && $childProduct->variationProductAttributes->count() > 0)
                                            @php
                                                $attributeText = [];
                                                foreach ($childProduct->variationProductAttributes as $attribute) {
                                                    $attributeText[] = $attribute->attribute_set_title . ': ' . $attribute->title;
                                                }
                                                $attributesString = '(' . implode(', ', $attributeText) . ')';
                                            @endphp
                                            data-attributes="{{ $attributesString }}"
                                        @endif
                                    >
                                    <button type="button" class="btn btn-sm btn-secondary increase-qty" @if($item->max_qty && $item->min_qty >= $item->max_qty) disabled @endif>+</button>
                                    <input type="checkbox" name="selected_products[{{ $childProduct->id }}]" id="product_selected_{{ $childProduct->id }}" class="selected-product-checkbox" style="display: none;" value="0">
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="summary mt-4">
            <div class="summary-title">{{ trans('plugins/mix-and-match::mix-and-match.selected_items') }} <span id="selected-count">0</span></div>
            <div class="summary-items" id="selected-items-list"></div>
            <div class="validation-message text-danger" id="validation-message"></div>
        </div>
    </div>

    <script>
        // Store the correct maximum container size globally
        window.correctMaxItems = {{ $maxItems ?: 'Infinity' }};
        
        // Override the native alert function to intercept and correct any incorrect maximum values
        const originalAlert = window.alert;
        window.alert = function(message) {
            console.log('Alert intercepted:', message);
            
            // Check if this is a maximum container size alert
            if (typeof message === 'string' && message.includes('You can select at most')) {
                // Replace any incorrect maximum value with our correct value
                const correctedMessage = message.replace(/at most \d+ items/, `at most ${window.correctMaxItems} items`);
                console.log('Corrected alert message:', correctedMessage);
                return originalAlert(correctedMessage);
            }
            
            // Otherwise, proceed with the original alert
            return originalAlert(message);
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            // Get all DOM elements
            const container = document.querySelector('.mix-and-match-container');
            const quantityInputs = document.querySelectorAll('.product-qty');
            const decreaseButtons = document.querySelectorAll('.decrease-qty');
            const increaseButtons = document.querySelectorAll('.increase-qty');
            const selectedCount = document.getElementById('selected-count');
            const selectedItemsList = document.getElementById('selected-items-list');
            const validationMessage = document.getElementById('validation-message');
            const addToCartForm = document.querySelector('form.cart-form');
            
            // Override the form action to use our custom mix and match cart endpoint
            if (addToCartForm) {
                console.log('Found cart form, changing action to mix-and-match endpoint');
                addToCartForm.action = '{{ route("public.mix-and-match.add-to-cart") }}';
            } else {
                console.error('Cart form not found!');
            }
            
            // Constants - ensure we use the global correct value
            const minItems = {{ $minItems }};
            const maxItems = window.correctMaxItems; // Use our global variable
            
            // Debug the actual values to console
            console.log('Mix and Match settings from backend:', {
                minItems: minItems,
                maxItems: maxItems,
                productId: {{ $product->id }}
            });
            
            // Force update any hardcoded values that might be in the DOM
            if (document.querySelector('.mix-and-match-container')) {
                document.querySelector('.mix-and-match-container').dataset.maxItems = maxItems;
            }
            
            // Add event listeners to quantity inputs
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const min = parseInt(this.getAttribute('min')) || 0;
                    const max = parseInt(this.getAttribute('max')) || 100;
                    let value = parseInt(this.value);
                    
                    // Validate the input value
                    if (isNaN(value) || value < min) {
                        value = min;
                    } else if (max && value > max) {
                        value = max;
                    }
                    
                    // Check if adding this value would exceed the maximum container size
                    const currentTotal = getCurrentTotalItems();
                    const oldValue = parseInt(this.dataset.lastValue || 0);
                    const difference = value - oldValue;
                    
                    if (maxItems !== Infinity && (currentTotal + difference) > maxItems) {
                        // Calculate the maximum value this input can have without exceeding the container limit
                        const maxAllowed = Math.max(0, maxItems - (currentTotal - oldValue));
                        value = maxAllowed;
                        alert(`You can select at most ${maxItems} items in total.`);
                    }
                    
                    this.value = value;
                    this.dataset.lastValue = value;
                    updateSummaryAndStore(); // Call the new function
                });
            });
            
            // Remove any existing event listeners from the buttons
            decreaseButtons.forEach(button => {
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
            });
            
            increaseButtons.forEach(button => {
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
            });
            
            // Get fresh references to the buttons
            const freshDecreaseButtons = document.querySelectorAll('.decrease-qty');
            const freshIncreaseButtons = document.querySelectorAll('.increase-qty');
            
            // Add event listeners to decrease buttons with a more direct approach
            freshDecreaseButtons.forEach(button => {
                button.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const input = this.nextElementSibling;
                    let value = parseInt(input.value);
                    const min = parseInt(input.getAttribute('min')) || 0;
                    
                    if (value > min) {
                        value = value - 1; // Explicitly decrement by 1
                        input.value = value;
                        input.dataset.lastValue = value;
                        updateSummaryAndStore();
                    }
                    
                    return false;
                };
            });
            
            // Add event listeners to increase buttons with a more direct approach
            freshIncreaseButtons.forEach(button => {
                button.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const input = this.previousElementSibling;
                    let value = parseInt(input.value);
                    const max = parseInt(input.getAttribute('max')) || 100;
                    
                    // Check if we're at or would exceed the maximum container size
                    const currentTotal = getCurrentTotalItems();
                    console.log('Increase button clicked - current total:', currentTotal, 'max allowed:', maxItems);
                    
                    if (maxItems !== Infinity && currentTotal >= maxItems) {
                        console.log('Preventing increase - would exceed max container size');
                        alert(`You can select at most ${maxItems} items for this mix and match product.`);
                        return false;
                    }
                    
                    if (!max || value < max) {
                        // Check if adding one more would exceed the max container size
                        if (maxItems !== Infinity && currentTotal + 1 > maxItems) {
                            alert(`You can select at most ${maxItems} items for this mix and match product.`);
                            return false;
                        }
                        
                        value = value + 1; // Explicitly increment by 1
                        input.value = value;
                        input.dataset.lastValue = value;
                        updateSummaryAndStore();
                    }
                    
                    return false;
                };
            });
            
            // Function to get current total selected items
            function getCurrentTotalItems() {
                let total = 0;
                quantityInputs.forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                console.log('Current total items:', total, 'Max allowed:', maxItems);
                return total;
            }
            
            // Function to update the summary
            function updateSummary() {
                let totalSelected = 0;
                let selectedItems = [];
                
                // Clear all hidden checkboxes first
                document.querySelectorAll('.selected-product-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.value = 0;
                });
                
                quantityInputs.forEach(input => {
                    const qty = parseInt(input.value);
                    if (qty > 0) {
                        // Get the product details directly from data attributes
                        const productId = input.dataset.productId;
                        const productName = input.dataset.productName;
                        const productImage = input.dataset.productImage || '';
                        const isVariation = input.dataset.isVariation === '1';
                        const attributes = input.dataset.attributes || '';
                        
                        // Set the hidden checkbox as checked for this product
                        const checkbox = document.getElementById('product_selected_' + productId);
                        if (checkbox) {
                            checkbox.checked = true;
                            checkbox.value = qty; // Store quantity in the checkbox value
                            console.log('Set checkbox for product ' + productId + ' to checked with qty ' + qty);
                        }
                        
                        // Create display name that includes variation attributes if present
                        let displayName = productName;
                        if (attributes) {
                            displayName = productName + ' ' + attributes;
                        }
                        
                        // Add to selected items
                        totalSelected += qty;
                        selectedItems.push({
                            id: productId,
                            name: displayName, // Use the full name with attributes
                            image: productImage,
                            is_variation: isVariation,
                            attributes: attributes,
                            qty: qty
                        });
                        
                        // Log for debugging
                        console.log('Added item:', {
                            id: productId,
                            name: displayName,
                            qty: qty,
                            attributes: attributes
                        });
                    }
                });
                
                // Update selected count
                if (selectedCount) {
                    selectedCount.textContent = totalSelected;
                }
                
                // Update the selected items list
                if (selectedItemsList) {
                    selectedItemsList.innerHTML = '';
                    
                    selectedItems.forEach(item => {
                        const itemElement = document.createElement('div');
                        itemElement.textContent = `${item.name} × ${item.qty}`;
                        selectedItemsList.appendChild(itemElement);
                    });
                }
                
                // Validation message
                if (validationMessage) {
                    if (totalSelected < minItems) {
                        validationMessage.textContent = `You need to select at least ${minItems} items (currently selected: ${totalSelected})`;
                        validationMessage.style.color = '#dc3545'; // Red color for error
                    } else if (maxItems !== Infinity && totalSelected > maxItems) {
                        validationMessage.textContent = `You can select at most ${maxItems} items (currently selected: ${totalSelected})`;
                        validationMessage.style.color = '#dc3545'; // Red color for error
                    } else {
                        // Valid selection
                        if (maxItems !== Infinity) {
                            validationMessage.textContent = `Selected ${totalSelected} of ${maxItems} maximum items`;
                            validationMessage.style.color = '#28a745'; // Green color for valid
                        } else {
                            validationMessage.textContent = '';
                        }
                    }
                }
                
                // Modify the cart form if it exists
                if (addToCartForm) {
                    // Remove any existing hidden inputs for mix and match items
                    const existingInputs = addToCartForm.querySelectorAll('input[name="mix_and_match_items"]');
                    existingInputs.forEach(input => input.remove());
                    
                    // Create a hidden input with the selected items
                    if (selectedItems.length > 0) {
                        // Store detailed information about each selected item
                        const mixAndMatchItems = {};
                        selectedItems.forEach(item => {
                            // Store more than just the quantity
                            mixAndMatchItems[item.id] = {
                                qty: item.qty,
                                name: item.name,
                                image: item.image || '',
                                is_variation: item.is_variation || false,
                                attributes: item.attributes || ''
                            };
                        });
                        
                        // Log for debugging
                        console.log('Mix and match items:', mixAndMatchItems);
                        
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'mix_and_match_items';
                        hiddenInput.value = JSON.stringify(mixAndMatchItems);
                        addToCartForm.appendChild(hiddenInput);
                    }
                }
            }
            
            // Define a global variable to store the current selected items
            let currentSelectedItems = [];
            
            // Update the summary function to store the current selected items
            function updateSummaryAndStore() {
                updateSummary();
                // Store the current selected items for later use
                currentSelectedItems = [];
                
                quantityInputs.forEach(input => {
                    const qty = parseInt(input.value);
                    if (qty > 0) {
                        const productId = input.dataset.productId;
                        const productName = input.dataset.productName;
                        const productImage = input.dataset.productImage || '';
                        const isVariation = input.dataset.isVariation === '1';
                        const attributes = input.dataset.attributes || '';
                        
                        currentSelectedItems.push({
                            id: productId,
                            name: productName,
                            image: productImage,
                            is_variation: isVariation,
                            attributes: attributes,
                            qty: qty
                        });
                    }
                });
            }
            
            // Call updateSummaryAndStore initially
            updateSummaryAndStore();
            
            // Override the add to cart form submission
            if (addToCartForm) {
                addToCartForm.addEventListener('submit', function(e) {
                    // Count selected items
                    let totalSelected = 0;
                    let hasSelectedItems = false;
                    
                    quantityInputs.forEach(input => {
                        const qty = parseInt(input.value);
                        if (qty > 0) {
                            totalSelected += qty;
                            hasSelectedItems = true;
                        }
                    });
                    
                    console.log('Form submission check - selected items:', totalSelected, 'max allowed:', maxItems);
                    
                    // Validate selection
                    if (!hasSelectedItems || totalSelected < minItems) {
                        e.preventDefault();
                        alert(`Please select at least ${minItems} items for this mix and match product.`);
                        return false;
                    }
                    
                    if (maxItems !== Infinity && totalSelected > maxItems) {
                        e.preventDefault();
                        alert(`You can select at most ${maxItems} items for this mix and match product.`);
                        return false;
                    }
                    
                    // Let the form submit naturally - no need for AJAX
                    console.log('Form is valid, submitting with selected items');
                    return true;
                });
            }
            
            // Initialize the summary
            updateSummary();
        });
    </script>
@endif
