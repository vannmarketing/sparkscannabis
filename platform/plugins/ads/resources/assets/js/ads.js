'use strict';

$(document).ready(function () {
    // Handle active days selection
    if ($('#active-days-container').length) {
        // Toggle active day buttons
        $('.active-day-button').on('click', function() {
            const day = $(this).data('day');
            const checkbox = $(this).find('input[type="checkbox"]');
            
            $(this).toggleClass('active');
            checkbox.prop('checked', !checkbox.prop('checked'));
        });
        
        // Ensure at least one checkbox is checked to avoid the "must be an array" error
        $('form').on('submit', function() {
            // If no checkboxes are checked, the hidden input with value "[]" will be submitted
            // This ensures active_days is always treated as an array
            return true;
        });
    }
});
