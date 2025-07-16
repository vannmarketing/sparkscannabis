/**
 * Simple fixes for Sparks Cannabis site
 * Minimal approach to fix order confirmation and login issues
 */
(function() {
    // Wait for document to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Fix for order confirmation buttons
        if (document.querySelector('.btn-confirm-order') || document.querySelector('#confirm-payment-order-button')) {
            // Add a simple reload function to all buttons that need it
            document.addEventListener('click', function(e) {
                // List of selectors that should trigger a page reload after AJAX
                const reloadSelectors = [
                    '.btn-confirm-order', 
                    '#confirm-payment-order-button',
                    '.btn-trigger-confirm-payment',
                    '.btn-update-order'
                ];
                
                // Check if the clicked element matches any of our selectors
                let needsReload = false;
                reloadSelectors.forEach(function(selector) {
                    if (e.target.matches(selector) || e.target.closest(selector)) {
                        needsReload = true;
                    }
                });
                
                // If this is a button that needs reload, set a timeout to reload the page
                if (needsReload) {
                    // Wait for the AJAX to complete (approximately 1.5 seconds)
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                }
            }, true); // Use capturing to ensure we get the event first
        }
        
        // Fix for Mix and Match product display on category/archive pages
        function cleanupMixAndMatchDisplay() {
            // Check if we're on a category/archive page (not on a single product page)
            if (document.querySelector('.shop-products-listing') && !document.querySelector('.product-detail-container')) {
                // Find all product cards
                const productCards = document.querySelectorAll('.product-inner');
                
                productCards.forEach(function(card) {
                    // Check if this is a Mix and Match product or has child products displayed
                    const isMixAndMatch = card.querySelector('[data-mix-and-match="1"]');
                    const hasSelectItems = Array.from(card.querySelectorAll('div')).some(div => 
                        div.textContent.includes('Select your items') || 
                        div.textContent.includes('Select between')
                    );
                    
                    if (isMixAndMatch || hasSelectItems) {
                        // Find the product-bottom-box and hide it on archive pages
                        const productBottomBox = card.querySelector('.product-bottom-box');
                        if (productBottomBox) {
                            // Use !important to override any hover styles
                            productBottomBox.style.cssText = 'display: none !important;';
                            
                            // Also add a class that we can target with CSS
                            card.classList.add('mix-match-hidden');
                            
                            // Add hover event to ensure it stays hidden
                            card.addEventListener('mouseenter', function() {
                                productBottomBox.style.cssText = 'display: none !important;';
                            });
                            
                            // Add click handler to the product card to navigate to product detail
                            card.addEventListener('click', function(e) {
                                // Don't trigger if clicking on add to cart or wishlist buttons
                                if (!e.target.closest('.add-to-cart-button') && 
                                    !e.target.closest('.wishlist-button') && 
                                    !e.target.closest('.quick-view-button')) {
                                    // Find the product link and navigate to it
                                    const productLink = card.querySelector('.product-name a');
                                    if (productLink && productLink.href) {
                                        window.location.href = productLink.href;
                                    }
                                }
                            });
                        }
                        
                        // Mobile-specific additional fixes
                        if (window.innerWidth < 768) {
                            // Hide all tables
                            const tables = card.querySelectorAll('table');
                            tables.forEach(table => table.style.display = 'none');
                            
                            // Hide all variation badges
                            const variationBadges = card.querySelectorAll('.badge.bg-info');
                            variationBadges.forEach(badge => badge.style.display = 'none');
                            
                            // Hide the "Select your items" heading and related text
                            const selectItemsHeadings = card.querySelectorAll('h4');
                            selectItemsHeadings.forEach(heading => {
                                if (heading.textContent.includes('Select your items')) {
                                    heading.style.display = 'none';
                                    
                                    // Also hide the paragraph that follows
                                    const nextParagraph = heading.nextElementSibling;
                                    if (nextParagraph && nextParagraph.tagName === 'P') {
                                        nextParagraph.style.display = 'none';
                                    }
                                }
                            });
                        }
                    }
                });
            }
        }
        
        // Initial cleanup
        cleanupMixAndMatchDisplay();
        
        // Also run on window resize to handle orientation changes
        window.addEventListener('resize', cleanupMixAndMatchDisplay);
        
        // Run again after a short delay to ensure all elements are loaded
        setTimeout(cleanupMixAndMatchDisplay, 500);
    });
})();
