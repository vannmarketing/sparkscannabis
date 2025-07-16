'use strict';

$(document).ready(function() {
    // Header messages rotation
    function initHeaderMessages() {
        if ($("#news-flash").length) {
            const newsFlash = $("#news-flash");
            const messages = newsFlash.find("ul li");
            
            console.log('Found ' + messages.length + ' header messages');
            
            if (messages.length > 1) {
                // Get rotation speed from theme options or use default 3 seconds
                let rotationSpeed = window.headerMessagesRotationSpeed || 3;
                console.log('Rotation speed: ' + rotationSpeed + ' seconds');
                
                // Force hide all messages first
                messages.css('display', 'none');
                
                // Show the first message
                messages.first().css('display', 'inline-block');
                
                let currentIndex = 0;
                
                // Set up rotation interval
                setInterval(function() {
                    // Hide all messages
                    messages.css('display', 'none');
                    
                    // Move to next message
                    currentIndex = (currentIndex + 1) % messages.length;
                    
                    // Show the current message
                    messages.eq(currentIndex).css('display', 'inline-block');
                    console.log('Showing message: ' + (currentIndex + 1));
                }, rotationSpeed * 1000); // Convert seconds to milliseconds
            }
        }
    }
    
    // Initialize header messages with a slight delay to ensure DOM is fully loaded
    setTimeout(initHeaderMessages, 500);
});
