import sanitizeHTML from 'sanitize-html'

export class DiscountManagement {
    init() {
        $(document).on('click', '.btn-open-coupon-form', (event) => {
            event.preventDefault()
            $(document).find('.coupon-wrapper').toggle()
        })

        $('.coupon-wrapper .coupon-code').keypress((event) => {
            if (event.keyCode === 13) {
                $('.apply-coupon-code').trigger('click')
                event.preventDefault()
                event.stopPropagation()
                return false
            }
        })

        // Handle applying coupon code
        $(document).on('click', '.apply-coupon-code', (event) => {
            event.preventDefault()
            this.handleCouponAction(event, 'apply')
        })

        // Handle removing coupon code
        $(document).on('click', '.remove-coupon-code', (event) => {
            event.preventDefault()
            this.handleCouponAction(event, 'remove')
        })

        // Handle quick-apply coupon from coupon list
        $(document).on('click', '[data-bb-toggle="apply-coupon-code"]', (event) => {
            event.preventDefault()
            
            const $button = $(event.currentTarget)
            $button.find('i').remove()
            $button.html(`<i class="fa fa-spin fa-spinner"></i> ${$button.html()}`)

            if ($(document).find('.remove-coupon-code').length) {
                $(document).find('.remove-coupon-code').trigger('click')
                // Wait for the removal to complete before applying new coupon
                setTimeout(() => {
                    this.applyCouponFromButton($button)
                }, 1000)
            } else {
                this.applyCouponFromButton($button)
            }
        })
    }

    // Helper method to apply coupon from a button
    applyCouponFromButton($button) {
        const discountCode = $button.data('discount-code')
        
        $(document).find('.coupon-wrapper').show()
        $('.coupon-wrapper .coupon-code').val(discountCode)
        
        // Trigger click on apply button
        $('.apply-coupon-code').trigger('click')
        
        // Remove spinner
        $button.find('i').remove()
    }

    // Handle coupon application or removal
    handleCouponAction(event, action) {
        const $button = $(event.currentTarget)
        const isApply = action === 'apply'
        
        // Show loading state
        $button.prop('disabled', true)
        $button.find('i').remove()
        $button.html(`<i class="fa fa-spin fa-spinner"></i> ${$button.html()}`)
        
        // Clear error messages
        $('.coupon-error-msg .text-danger').html('')
        
        let url, data
        
        if (isApply) {
            url = $button.data('url')
            const couponCode = $button.closest('.coupon-wrapper').find('.coupon-code').val()
            data = {
                coupon_code: couponCode,
                token: $('#checkout-token').val()
            }
        } else {
            url = $button.data('url')
            data = {
                token: $('#checkout-token').val()
            }
        }
        
        // Add overlay to indicate loading
        const $checkoutArea = $('.checkout-content-wrap')
        $checkoutArea.append('<div class="coupon-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; display: flex; justify-content: center; align-items: center;"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>')
        
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.error) {
                    // Show error message
                    $('.coupon-error-msg .text-danger').html(sanitizeHTML(response.message))
                    $button.prop('disabled', false)
                    $button.find('i').remove()
                    $('.coupon-overlay').remove()
                } else {
                    // Success - reload the page to ensure everything is updated correctly
                    const successMessage = response.message || (isApply ? 'Coupon applied successfully!' : 'Coupon removed successfully!')
                    
                    // Store success message in sessionStorage to display after reload
                    sessionStorage.setItem('couponMessage', successMessage)
                    
                    // Reload the page
                    window.location.reload()
                }
            },
            error: (xhr) => {
                let errorMessage = 'An error occurred. Please try again.'
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors
                    errorMessage = Object.values(errors)[0][0] || errorMessage
                }
                
                $('.coupon-error-msg .text-danger').html(sanitizeHTML(errorMessage))
                $button.prop('disabled', false)
                $button.find('i').remove()
                $('.coupon-overlay').remove()
            }
        })
    }
}

// Display stored success message after page reload
$(document).ready(function() {
    const couponMessage = sessionStorage.getItem('couponMessage')
    if (couponMessage) {
        // Display the message
        if (typeof MainCheckout !== 'undefined' && MainCheckout.showNotice) {
            MainCheckout.showNotice('success', couponMessage)
        } else {
            toastr.success(couponMessage)
        }
        
        // Clear the message from storage
        sessionStorage.removeItem('couponMessage')
    }
})
