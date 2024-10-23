// assets/js/notifi-plates.js
(function ($) {
    'use strict';

    let notifications = [];
    let currentNotification = null;
    let isDisplaying = false;

    function fetchNewPlates() {
        $.ajax({
            url: notifiPlatesAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_new_plates',
                nonce: notifiPlatesAjax.nonce
            },
            success: function (response) {
                if (response.success && response.data.length > 0) {
                    // Add new notifications to the queue
                    notifications = notifications.concat(
                        response.data.filter(notification =>
                            !notifications.some(n => n.id === notification.id)
                        )
                    );

                    // Start displaying if not already doing so
                    if (!isDisplaying) {
                        displayNextNotification();
                    }
                }
            }
        });
    }

    function displayNextNotification() {
        if (notifications.length === 0) {
            isDisplaying = false;
            return;
        }

        isDisplaying = true;
        currentNotification = notifications.shift();

        const $plate = $('.notifi-plate');
        const $plateInfo = $plate.find('.plate-info');

        // Update notification content
        $plateInfo.html(`
            Welcome ${currentNotification.type}  <strong>${currentNotification.dog_name}</strong> from
            <strong>${currentNotification.state}</strong> Just Now
            `);
        // ${currentNotification.date}

        // Show notification
        $plate.fadeIn(300).addClass('active');

        // Hide after 5 seconds
        setTimeout(() => {
            $plate.removeClass('active').fadeOut(300, () => {
                // Display next notification after current one fades out
                setTimeout(displayNextNotification, 1000);
            });
        }, 5000);
    }

    // Fetch new plates every 15 seconds
    setInterval(fetchNewPlates, 20000);

    // Initial fetch
    fetchNewPlates();

})(jQuery);