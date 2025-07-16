// Script to control slider height with aggressive style overrides
document.addEventListener('DOMContentLoaded', function() {
    // Create link element for custom slider CSS with high priority
    var customSliderCSS = document.createElement('link');
    customSliderCSS.rel = 'stylesheet';
    customSliderCSS.href = '/css/custom-slider.css?v=' + new Date().getTime(); // Add timestamp to prevent caching
    customSliderCSS.type = 'text/css';
    
    // Append to head
    document.head.appendChild(customSliderCSS);
    
    // Function to force slider heights based on screen size
    function forceSliderHeights() {
        // Target all possible slider elements
        const targets = [
            '.simple-slider-wrapper .owl-slider .slider-item',
            '.simple-slider-wrapper .owl-slider',
            '.slider-item',
            '.owl-carousel .slider-item',
            '.owl-carousel .owl-item .slider-item',
            '.owl-carousel .owl-stage-outer',
            '.owl-carousel .owl-stage'
        ];
        
        // Target all possible image elements
        const imageTargets = [
            '.simple-slider-wrapper .owl-slider .slider-item img',
            '.slider-item img',
            '.owl-carousel .slider-item img',
            '.owl-carousel .owl-item .slider-item img',
            '.owl-carousel .owl-item img'
        ];
        
        // Determine height based on screen width
        let height = '600px'; // Default height
        
        if (window.innerWidth >= 1200) {
            height = '1200px'; // Large desktop
        } else if (window.innerWidth >= 768 && window.innerWidth < 1200) {
            height = '500px'; // Tablets and small desktops
        } else if (window.innerWidth < 768) {
            height = '700px'; // Mobile phones
        }
        
        console.log('Setting slider height to: ' + height);
        
        // Apply styles to all possible slider elements
        targets.forEach(selector => {
            document.querySelectorAll(selector).forEach(element => {
                element.style.setProperty('height', height, 'important');
                element.style.setProperty('max-height', 'none', 'important');
                element.style.setProperty('overflow', 'visible', 'important');
            });
        });
        
        // Apply styles to all possible image elements
        imageTargets.forEach(selector => {
            document.querySelectorAll(selector).forEach(element => {
                element.style.setProperty('height', '100%', 'important');
                element.style.setProperty('object-fit', 'cover', 'important');
                element.style.setProperty('width', '100%', 'important');
            });
        });
    }
    
    // Apply styles immediately
    forceSliderHeights();
    
    // Apply styles after a short delay to ensure all elements are loaded
    setTimeout(forceSliderHeights, 500);
    
    // Apply styles after a longer delay to catch any late-loading elements
    setTimeout(forceSliderHeights, 1500);
    
    // Apply styles on window resize
    window.addEventListener('resize', forceSliderHeights);
    
    // Create a MutationObserver to watch for DOM changes and reapply styles
    const observer = new MutationObserver(function(mutations) {
        forceSliderHeights();
    });
    
    // Start observing the document with the configured parameters
    observer.observe(document.body, { 
        childList: true, 
        subtree: true 
    });
    
    console.log('Custom slider height controller loaded');
});
