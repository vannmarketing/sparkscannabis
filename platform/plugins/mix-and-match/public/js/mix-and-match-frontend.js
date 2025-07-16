(function ($) {
    'use strict';

    let MixAndMatchFrontend = {
        init: function () {
            this.initQuantityButtons();
            this.initSummaryUpdates();
            this.initFormSubmission();
        },

        initQuantityButtons: function () {
            const $container = $('.mix-and-match-container');
            
            // Increase quantity
            $container.on('click', '.increase-qty', function () {
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
                    MixAndMatchFrontend.updateSummary();
                }
            });
            
            // Decrease quantity
            $container.on('click', '.decrease-qty', function () {
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
                    MixAndMatchFrontend.updateSummary();
                }
            });
        },

        initSummaryUpdates: function () {
            // Initialize summary on page load
            this.updateSummary();
        },

        updateSummary: function () {
            const $container = $('.mix-and-match-container');
            const $selectedCount = $('#selected-count');
            const $selectedItemsList = $('#selected-items-list');
            const $selectedItemsInputs = $('#selected-items-inputs');
            const $validationMessage = $('#validation-message');
            const $addToCartButton = $('#add-to-cart-button');
            
            const minItems = parseInt($container.data('min-items') || 1);
            const maxItems = parseInt($container.data('max-items') || Infinity);
            const pricingType = $container.data('pricing-type');
            const fixedPrice = parseFloat($container.data('fixed-price') || 0);
            
            const selectedItems = [];
            let totalSelected = 0;
            let totalPrice = 0;
            
            $selectedItemsInputs.empty();
            
            $('.product-item').each(function () {
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
                            <span>${pricingType === 'per_item' ? this.formatPrice(item.price * item.qty) : ''}</span>
                        </div>
                    `);
                });
                
                // Add total price
                if (pricingType === 'per_item') {
                    $selectedItemsList.append(`
                        <div class="summary-item font-weight-bold mt-2">
                            <span>Total:</span>
                            <span>${this.formatPrice(totalPrice)}</span>
                        </div>
                    `);
                } else {
                    $selectedItemsList.append(`
                        <div class="summary-item font-weight-bold mt-2">
                            <span>Fixed Price:</span>
                            <span>${this.formatPrice(fixedPrice)}</span>
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
                        ? 'Please select at least one item'
                        : `Please select at least ${minItems} items`
                    );
                } else if (maxItems !== Infinity && totalSelected > maxItems) {
                    $validationMessage.text(`You can select at most ${maxItems} items`);
                }
            }
        },

        initFormSubmission: function () {
            const $form = $('#mix-and-match-form');
            const $validationMessage = $('#validation-message');
            
            $form.on('submit', function (e) {
                e.preventDefault();
                
                // Submit the form via AJAX
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function (res) {
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
                    error: function (res) {
                        $validationMessage.text(res.responseJSON.message || 'Error adding to cart');
                    }
                });
            });
        },

        formatPrice: function (price) {
            return price.toLocaleString('en-US', {
                style: 'currency',
                currency: 'USD', // This should be dynamic based on your store's currency
                minimumFractionDigits: 2
            });
        }
    };

    $(document).ready(function () {
        MixAndMatchFrontend.init();
    });

})(jQuery);
