/**
 * Enhanced Coupon Handler for Mobile Devices
 * Implements a direct XMLHttpRequest approach with improved error handling and loading states
 */
class CouponHandler {
    constructor() {
        this.setupEventListeners();
        this.displayStoredMessages();
    }

    /**
     * Initialize event listeners with capturing to intercept before jQuery
     */
    setupEventListeners() {
        // Remove existing handlers by cloning elements
        this.replaceElements('.apply-coupon-code');
        this.replaceElements('.remove-coupon-code');
        this.replaceElements('[data-bb-toggle="apply-coupon-code"]');

        // Add global event listener with capturing
        document.addEventListener('click', (event) => {
            const applyButton = event.target.closest('.apply-coupon-code');
            const removeButton = event.target.closest('.remove-coupon-code');
            const quickApplyButton = event.target.closest('[data-bb-toggle="apply-coupon-code"]');

            if (applyButton) {
                event.preventDefault();
                event.stopPropagation();
                this.handleCouponAction(applyButton, 'apply');
            } else if (removeButton) {
                event.preventDefault();
                event.stopPropagation();
                this.handleCouponAction(removeButton, 'remove');
            } else if (quickApplyButton) {
                event.preventDefault();
                event.stopPropagation();
                this.handleQuickApply(quickApplyButton);
            }
        }, true);
    }

    /**
     * Replace elements to remove existing event listeners
     */
    replaceElements(selector) {
        document.querySelectorAll(selector).forEach(element => {
            const clone = element.cloneNode(true);
            if (element.parentNode) {
                element.parentNode.replaceChild(clone, element);
            }
        });
    }

    /**
     * Handle quick apply button clicks
     */
    handleQuickApply(button) {
        const code = button.getAttribute('data-discount-code');
        if (!code) {
            this.showMessage('Error: No coupon code provided', 'error');
            return;
        }

        // Show loading state
        this.setButtonLoading(button, true);

        // Check for existing coupon
        const removeButton = document.querySelector('.remove-coupon-code');
        if (removeButton) {
            // Remove existing coupon first
            this.handleCouponAction(removeButton, 'remove', () => {
                setTimeout(() => this.applyCouponCode(code), 500);
            });
        } else {
            this.applyCouponCode(code);
        }
    }

    /**
     * Apply a coupon code
     */
    applyCouponCode(code) {
        const wrapper = document.querySelector('.coupon-wrapper');
        if (!wrapper) return;

        const input = wrapper.querySelector('.coupon-code');
        const applyButton = wrapper.querySelector('.apply-coupon-code');
        
        if (input && applyButton) {
            input.value = code;
            this.handleCouponAction(applyButton, 'apply');
        }
    }

    /**
     * Handle coupon application or removal
     */
    handleCouponAction(button, action, callback = null) {
        // Prevent double submission
        if (button.disabled) return;

        const isApply = action === 'apply';
        const originalHtml = button.innerHTML;
        
        // Show loading states
        this.setButtonLoading(button, true);
        this.showOverlay();

        // Prepare request data
        const formData = new FormData();
        const url = button.getAttribute('data-url');

        if (isApply) {
            const input = button.closest('.coupon-wrapper').querySelector('.coupon-code');
            if (!input || !input.value) {
                this.showMessage('Please enter a coupon code', 'error');
                this.setButtonLoading(button, false, originalHtml);
                this.hideOverlay();
                return;
            }
            formData.append('coupon_code', input.value);
        }

        // Add checkout token if available
        const tokenInput = document.getElementById('checkout-token');
        if (tokenInput) {
            formData.append('token', tokenInput.value);
        }

        // Make request using XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        
        // Set headers
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        }
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        this.showMessage(response.message || 'Error applying coupon', 'error');
                        this.setButtonLoading(button, false, originalHtml);
                        if (callback) callback(false);
                    } else {
                        // Store success message and reload
                        sessionStorage.setItem('couponMessage', response.message || 'Coupon applied successfully');
                        if (callback) {
                            callback(true);
                        } else {
                            window.location.reload();
                        }
                    }
                } catch (e) {
                    this.showMessage('Invalid server response', 'error');
                    this.setButtonLoading(button, false, originalHtml);
                    if (callback) callback(false);
                }
            } else {
                this.showMessage('Server error occurred', 'error');
                this.setButtonLoading(button, false, originalHtml);
                if (callback) callback(false);
            }
            this.hideOverlay();
        };

        xhr.onerror = () => {
            this.showMessage('Network error occurred', 'error');
            this.setButtonLoading(button, false, originalHtml);
            this.hideOverlay();
            if (callback) callback(false);
        };

        xhr.send(formData);
    }

    /**
     * Show loading overlay
     */
    showOverlay() {
        this.hideOverlay(); // Remove any existing overlay
        const overlay = document.createElement('div');
        overlay.className = 'coupon-overlay';
        overlay.innerHTML = '<div class="coupon-loader"></div>';
        document.body.appendChild(overlay);
    }

    /**
     * Hide loading overlay
     */
    hideOverlay() {
        const overlay = document.querySelector('.coupon-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * Set button loading state
     */
    setButtonLoading(button, isLoading, originalHtml = null) {
        button.disabled = isLoading;
        if (isLoading) {
            button.classList.add('loading-coupon');
            if (!button.querySelector('.fa-spinner')) {
                button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
            }
        } else {
            button.classList.remove('loading-coupon');
            if (originalHtml) {
                button.innerHTML = originalHtml;
            }
        }
    }

    /**
     * Show message to user
     */
    showMessage(message, type = 'success') {
        const existingMsg = document.querySelector('.coupon-message');
        if (existingMsg) {
            existingMsg.remove();
        }

        const msgElement = document.createElement('div');
        msgElement.className = `coupon-message ${type}`;
        msgElement.textContent = message;
        document.body.appendChild(msgElement);

        setTimeout(() => {
            msgElement.remove();
        }, 5000);
    }

    /**
     * Display stored messages (after page reload)
     */
    displayStoredMessages() {
        const message = sessionStorage.getItem('couponMessage');
        if (message) {
            this.showMessage(message);
            sessionStorage.removeItem('couponMessage');
        }
    }
}

// Initialize the coupon handler when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.checkout-content-wrap')) {
        window.couponHandler = new CouponHandler();
    }
});
