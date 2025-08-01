/**
 * Public JavaScript for Yenolx Restaurant Reservation System
 *
 * This file handles the interactivity of the public-facing booking form,
 * including AJAX calls for time slots and form submission.
 *
 * @package YRR/Assets
 * @since 1.6.0
 */

jQuery(document).ready(function($) {

    const form = $('#yrr-booking-form');
    const dateInput = $('#yrr-date');
    const partySizeInput = $('#yrr-party-size');
    const timeSlotGroup = $('#yrr-time-slot-group');
    const timeSlotContainer = $('#yrr-time-slots');
    const selectedTimeInput = $('#yrr-selected-time');
    const customerDetails = $('#yrr-customer-details');
    const submitButton = $('#yrr-submit-booking');
    const messages = $('#yrr-form-messages');

    // --- Step 1: Fetch available time slots ---

    function fetchTimeSlots() {
        const date = dateInput.val();
        const partySize = partySizeInput.val();

        if (!date || !partySize) {
            return;
        }

        // Show loading state
        timeSlotGroup.show();
        timeSlotContainer.html('<p class="yrr-loading-slots">' + yrr_public_ajax.strings.loading + '</p>');
        customerDetails.hide();
        submitButton.prop('disabled', true);

        $.ajax({
            url: yrr_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'yrr_get_available_slots',
                nonce: yrr_public_ajax.nonce,
                date: date,
                party_size: partySize
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let slotsHtml = '';
                    response.data.forEach(function(slot) {
                        slotsHtml += '<div class="yrr-time-slot" data-time="' + slot.value + '">' + slot.label + '</div>';
                    });
                    timeSlotContainer.html(slotsHtml);
                } else {
                    timeSlotContainer.html('<p class="yrr-no-slots">' + yrr_public_ajax.strings.no_slots + '</p>');
                }
            },
            error: function() {
                timeSlotContainer.html('<p class="yrr-no-slots">' + yrr_public_ajax.strings.error + '</p>');
            }
        });
    }

    // Fetch slots when date or party size changes
    dateInput.on('change', fetchTimeSlots);
    partySizeInput.on('change', fetchTimeSlots);

    // --- Step 2: Handle time slot selection ---

    timeSlotContainer.on('click', '.yrr-time-slot', function() {
        // Handle selection UI
        $('.yrr-time-slot').removeClass('selected');
        $(this).addClass('selected');

        // Store selected time
        selectedTimeInput.val($(this).data('time'));

        // Show customer details and enable submit button
        customerDetails.show();
        submitButton.prop('disabled', false);
    });

    // --- Step 3: Handle final form submission ---

    form.on('submit', function(e) {
        e.preventDefault();
        
        // Show loading state on button
        submitButton.prop('disabled', true).text(yrr_public_ajax.strings.loading);

        $.ajax({
            url: yrr_public_ajax.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=yrr_create_reservation&nonce=' + yrr_public_ajax.nonce,
            success: function(response) {
                if (response.success) {
                    messages.removeClass('error').addClass('success').text(response.data.message).show();
                    form[0].reset();
                    customerDetails.hide();
                    timeSlotContainer.html('<p class="yrr-loading-slots"><?php _e('Select a date and party size to see available times.', 'yrr'); ?></p>');
                } else {
                    messages.removeClass('success').addClass('error').text(response.data.message).show();
                    submitButton.prop('disabled', false).text('Complete Reservation');
                }
            },
            error: function() {
                messages.removeClass('success').addClass('error').text(yrr_public_ajax.strings.error).show();
                submitButton.prop('disabled', false).text('Complete Reservation');
            }
        });
    });
});
