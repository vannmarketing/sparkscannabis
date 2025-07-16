class FreeGifts {
    constructor() {
        this.init();
    }

    init() {
        this.registerEvents();
        this.checkEligibleGifts();
    }

    registerEvents() {
        // Eligibility notice click
        $(document).on('click', '.free-gifts-eligibility-notice', () => {
            this.showGiftSelectionModal();
        });

        // Add gift button click
        $(document).on('click', '.add-gift-btn', (e) => {
            const $button = $(e.currentTarget);
            const productId = $button.data('product-id');
            const giftRuleId = $button.data('gift-rule-id');
            const quantity = $button.closest('.gift-item').find('.gift-quantity').val() || 1;

            this.addGiftToCart(productId, quantity, giftRuleId);
        });

        // Remove gift button click
        $(document).on('click', '.remove-gift-btn', (e) => {
            const $button = $(e.currentTarget);
            const rowId = $button.data('row-id');

            this.removeGiftFromCart(rowId);
        });

        // Cart updated event
        $(document).on('cart_updated', () => {
            this.checkEligibleGifts();
        });
    }

    checkEligibleGifts() {
        $.ajax({
            url: route('public.ajax.free-gifts.eligible-gifts'),
            type: 'GET',
            success: (response) => {
                if (response.data && response.data.gifts && response.data.gifts.length > 0) {
                    this.showEligibilityNotice();
                } else {
                    this.hideEligibilityNotice();
                }
            }
        });
    }

    showEligibilityNotice() {
        const noticeEnabled = window.freeGiftsSettings.eligibility_notice_enabled;
        
        if (!noticeEnabled) {
            return;
        }

        const noticeText = window.freeGiftsSettings.eligibility_notice_text;
        
        if ($('.free-gifts-eligibility-notice').length === 0) {
            const notice = `
                <div class="free-gifts-eligibility-notice">
                    <i class="fa fa-gift"></i> ${noticeText}
                </div>
            `;
            
            // Add notice to cart page
            $('.cart-page .cart-content').prepend(notice);
            
            // Add notice to checkout page
            $('.checkout-content-wrap').prepend(notice);
        }
    }

    hideEligibilityNotice() {
        $('.free-gifts-eligibility-notice').remove();
    }

    showGiftSelectionModal() {
        $.ajax({
            url: route('public.ajax.free-gifts.eligible-gifts'),
            type: 'GET',
            success: (response) => {
                if (response.data && response.data.gifts && response.data.gifts.length > 0) {
                    this.renderGiftSelectionModal(response.data.gifts, response.data.rules);
                } else {
                    Botble.showError(window.trans['plugins.free-gifts.no_eligible_gifts']);
                }
            }
        });
    }

    renderGiftSelectionModal(gifts, rules) {
        const settings = window.freeGiftsSettings;
        const title = settings.gift_selection_title;
        const description = settings.gift_selection_description;
        const displayType = settings.display_type;
        
        let giftsHtml = '';
        
        if (displayType === 'table') {
            giftsHtml = this.renderGiftsTable(gifts, rules);
        } else if (displayType === 'carousel') {
            giftsHtml = this.renderGiftsCarousel(gifts, rules);
        } else {
            giftsHtml = this.renderGiftsDropdown(gifts, rules);
        }
        
        const modalContent = `
            <div class="free-gifts-modal-content">
                <h4>${title}</h4>
                <p>${description}</p>
                ${giftsHtml}
            </div>
        `;
        
        Botble.showModal(modalContent, title, 'modal-lg');
    }

    renderGiftsTable(gifts, rules) {
        let html = `
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        gifts.forEach(gift => {
            const rule = rules.find(r => r.id === gift.gift_rule_id);
            const maxQuantity = rule ? rule.max_gifts_per_order || 1 : 1;
            const quantityInput = window.freeGiftsSettings.allow_multiple_gift_quantities 
                ? `<input type="number" class="form-control gift-quantity" min="1" max="${maxQuantity}" value="1">`
                : `<input type="hidden" class="gift-quantity" value="1"><span>1</span>`;
            
            html += `
                <tr class="gift-item">
                    <td><img src="${gift.image}" alt="${gift.name}" width="50"></td>
                    <td>${gift.name}</td>
                    <td>${quantityInput}</td>
                    <td>
                        <button class="btn btn-primary add-gift-btn" data-product-id="${gift.id}" data-gift-rule-id="${gift.gift_rule_id}">
                            ${window.freeGiftsSettings.add_gift_button_text}
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        return html;
    }

    renderGiftsCarousel(gifts, rules) {
        let html = `
            <div class="free-gifts-carousel">
                <div class="row">
        `;
        
        gifts.forEach(gift => {
            const rule = rules.find(r => r.id === gift.gift_rule_id);
            const maxQuantity = rule ? rule.max_gifts_per_order || 1 : 1;
            const quantityInput = window.freeGiftsSettings.allow_multiple_gift_quantities 
                ? `<input type="number" class="form-control gift-quantity" min="1" max="${maxQuantity}" value="1">`
                : `<input type="hidden" class="gift-quantity" value="1"><span>1</span>`;
            
            html += `
                <div class="col-md-4 col-sm-6 gift-item">
                    <div class="gift-card">
                        <div class="gift-image">
                            <img src="${gift.image}" alt="${gift.name}">
                        </div>
                        <div class="gift-details">
                            <h5>${gift.name}</h5>
                            <div class="gift-quantity-wrapper">
                                <label>Quantity:</label>
                                ${quantityInput}
                            </div>
                            <button class="btn btn-primary add-gift-btn" data-product-id="${gift.id}" data-gift-rule-id="${gift.gift_rule_id}">
                                ${window.freeGiftsSettings.add_gift_button_text}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
        
        return html;
    }

    renderGiftsDropdown(gifts, rules) {
        let html = `
            <div class="free-gifts-dropdown">
                <select class="form-control gift-select">
                    <option value="">-- Select a gift --</option>
        `;
        
        gifts.forEach(gift => {
            html += `<option value="${gift.id}" data-rule-id="${gift.gift_rule_id}">${gift.name}</option>`;
        });
        
        html += `
                </select>
                <div class="gift-quantity-wrapper mt-2">
                    <label>Quantity:</label>
                    <input type="number" class="form-control gift-quantity" min="1" value="1">
                </div>
                <button class="btn btn-primary add-selected-gift-btn mt-2">
                    ${window.freeGiftsSettings.add_gift_button_text}
                </button>
            </div>
        `;
        
        // Add event listener for the dropdown selection
        setTimeout(() => {
            $('.add-selected-gift-btn').on('click', () => {
                const productId = $('.gift-select').val();
                const giftRuleId = $('.gift-select option:selected').data('rule-id');
                const quantity = $('.gift-quantity').val() || 1;
                
                if (productId) {
                    this.addGiftToCart(productId, quantity, giftRuleId);
                } else {
                    Botble.showError('Please select a gift');
                }
            });
        }, 300);
        
        return html;
    }

    addGiftToCart(productId, quantity, giftRuleId) {
        $.ajax({
            url: route('public.ajax.free-gifts.add-to-cart'),
            type: 'POST',
            data: {
                product_id: productId,
                quantity: quantity,
                gift_rule_id: giftRuleId
            },
            success: (response) => {
                if (response.error) {
                    Botble.showError(response.message);
                } else {
                    Botble.showSuccess(response.message || window.freeGiftsSettings.gift_added_text);
                    $('.modal-backdrop').remove();
                    $('.modal').modal('hide');
                    
                    // Refresh cart
                    window.loadAjaxCart();
                }
            },
            error: (error) => {
                Botble.handleError(error);
            }
        });
    }

    removeGiftFromCart(rowId) {
        $.ajax({
            url: route('public.ajax.free-gifts.remove-from-cart'),
            type: 'POST',
            data: {
                row_id: rowId
            },
            success: (response) => {
                if (response.error) {
                    Botble.showError(response.message);
                } else {
                    Botble.showSuccess(response.message);
                    
                    // Refresh cart
                    window.loadAjaxCart();
                }
            },
            error: (error) => {
                Botble.handleError(error);
            }
        });
    }
}

$(document).ready(() => {
    new FreeGifts();
});
