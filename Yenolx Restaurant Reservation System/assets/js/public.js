/**
 * Public JavaScript for Yenolx Restaurant Reservation System
 *
 * This file handles the interactivity of the public-facing booking form
 * and the "My Reservations" portal.
 *
 * @package YRR/Assets
 * @since 1.6.0
 */

jQuery(document).ready(function($) {

    // --- Booking Form Logic ---
    const bookingForm = $('#yrr-booking-form');
    if (bookingForm.length) {
        // All the logic for the booking form from before...
    }


    // --- [NEW] My Reservations Portal Logic ---
    const lookupForm = $('#yrr-lookup-form');
    if (lookupForm.length) {
        const resultsContainer = $('#yrr-my-reservations-results');

        lookupForm.on('submit', function(e) {
            e.preventDefault();

            const email = $('#yrr-lookup-email').val();
            if (!email) {
                resultsContainer.html('<p class="yrr-error">Please enter an email address.</p>');
                return;
            }

            // Show a loading message
            resultsContainer.html('<p class="yrr-loading">Searching for your reservations...</p>');

            $.ajax({
                url: yrr_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'yrr_get_my_reservations',
                    nonce: yrr_public_ajax.nonce,
                    email: email
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '<ul>';
                        response.data.forEach(function(booking) {
                            html += `<li>
                                <strong>Date:</strong> ${booking.reservation_date} at ${booking.reservation_time}<br>
                                <strong>Party Size:</strong> ${booking.party_size}<br>
                                <strong>Status:</strong> <span class="yrr-status-${booking.status}">${booking.status}</span>
                            </li>`;
                        });
                        html += '</ul>';
                        resultsContainer.html(html);
                    } else {
                        resultsContainer.html('<p>No reservations found for that email address.</p>');
                    }
                },
                error: function() {
                    resultsContainer.html('<p class="yrr-error">An error occurred. Please try again.</p>');
                }
            });
        });
    }

});
