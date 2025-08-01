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
    }).trigger('change'); // Trigger on page load to set initial state


    // --- Weekly Calendar View ---

    // Check if the calendar container exists on the current page
    const calendarEl = document.getElementById('yrr-calendar-wrapper');
    if (calendarEl) {
        // NOTE: This section requires a calendar library like FullCalendar.js
        // You would typically enqueue the library's JS and CSS files first.
        
        // Placeholder message until a library is integrated
        $(calendarEl).html('<div class="yrr-placeholder-content"><p><strong>Calendar functionality requires integration with a JavaScript library like FullCalendar.js.</strong></p><p>Once integrated, this area will display an interactive weekly schedule.</p></div>');
        
        /*
        // --- EXAMPLE FullCalendar.js Integration ---
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
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
            eventClick: function(info) {
                // Handle click on a reservation event (e.g., open a modal)
                alert('Reservation ID: ' + info.event.id + '\\nCustomer: ' + info.event.title);
            },
            dateClick: function(info) {
                // Handle click on a date/time slot (e.g., open 'add reservation' modal)
                alert('Clicked on: ' + info.dateStr);
            }
        });

        calendar.render();

        // Custom navigation button hooks
        $('#yrr-calendar-prev').on('click', () => calendar.prev());
        $('#yrr-calendar-today').on('click', () => calendar.today());
        $('#yrr-calendar-next').on('click', () => calendar.next());
        */
    }

});
