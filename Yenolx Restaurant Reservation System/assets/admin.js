/**
 * Admin JavaScript for Yenolx Restaurant Reservation System
 *
 * This file handles all client-side interactivity for the admin dashboard,
 * including the calendar, AJAX actions, and UI enhancements.
 *
 * @package YRR/Assets
 * @since 1.6.0
 */

jQuery(document).ready(function($) {

    // --- General Admin UI Enhancements ---

    // Confirm before deleting items
    $('.submitdelete').on('click', function(e) {
        if (!confirm(yrr_admin.strings.confirm_delete)) {
            e.preventDefault();
        }
    });

    // --- Operating Hours Page ---

    // Toggle time inputs based on the "Open/Closed" status for each day
    $('.yrr-hours-table .day-status').on('change', function() {
        const row = $(this).closest('tr');
        const timeInputs = row.find('.time-input');
        const isClosed = $(this).val() === '1';

        timeInputs.prop('disabled', isClosed);
        if (isClosed) {
            row.addClass('yrr-day-closed');
        } else {
            row.removeClass('yrr-day-closed');
        }
    }).trigger('change');


    // --- Weekly Calendar View ---

    const calendarEl = document.getElementById('yrr-calendar-wrapper');
    if (calendarEl) {
        // NOTE: This section requires a calendar library like FullCalendar.js
        // For this example, we assume FullCalendar.js is loaded.
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            editable: true, // Make events draggable
            droppable: true, // Allow events to be dropped

            // Fetch events from our AJAX endpoint
            events: function(fetchInfo, successCallback, failureCallback) {
                $.ajax({
                    url: yrr_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'yrr_get_calendar_reservations',
                        nonce: yrr_admin.nonce,
                        start: fetchInfo.startStr,
                        end: fetchInfo.endStr
                    },
                    success: function(response) {
                        if (response.success) {
                            successCallback(response.data);
                        } else {
                            failureCallback(new Error('Failed to fetch reservations.'));
                        }
                    }
                });
            },

            // ** NEW: Handle the drag-and-drop event **
            eventDrop: function(info) {
                if (!confirm(yrr_admin.strings.confirm_reschedule)) {
                    info.revert(); // Revert the change if the user cancels
                    return;
                }

                $.ajax({
                    url: yrr_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'yrr_update_reservation_time',
                        nonce: yrr_admin.nonce,
                        reservation_id: info.event.id,
                        new_date: info.event.start.toISOString().slice(0, 10),
                        new_time: info.event.start.toTimeString().slice(0, 8)
                    },
                    success: function(response) {
                        if (!response.success) {
                            alert(yrr_admin.strings.error);
                            info.revert(); // Revert if the server-side update fails
                        }
                    },
                    error: function() {
                        alert(yrr_admin.strings.error);
                        info.revert();
                    }
                });
            },

            eventClick: function(info) {
                // Future enhancement: Open a modal to edit details
                alert('Reservation ID: ' + info.event.id + '\\nCustomer: ' + info.event.title);
            }
        });

        calendar.render();

        // Custom navigation button hooks
        $('#yrr-calendar-prev').on('click', () => calendar.prev());
        $('#yrr-calendar-today').on('click', () => calendar.today());
        $('#yrr-calendar-next').on('click', () => calendar.next());
    }

});
