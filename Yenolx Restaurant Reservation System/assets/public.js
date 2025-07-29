/**
 * Yenolx Restaurant Reservation System v1.6 - Public JavaScript
 * Powers the interactive booking form and customer reservation management
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        YRR_Public.init();
    });

    /**
     * Main Public JavaScript Object
     */
    window.YRR_Public = {
        
        /**
         * Current form data storage
         */
        formData: {
            step: 1,
            location_id: 1,
            party_size: null,
            reservation_date: null,
            reservation_time: null,
            customer_name: null,
            customer_email: null,
            customer_phone: null,
            special_requests: null
        },

        /**
         * Initialize all public functionality
         */
        init: function() {
            this.bindEvents();
            this.initDatePickers();
            this.loadInitialData();
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            // Form navigation
            $(document).on('click', '.yrr-time-slot', this.selectTimeSlot);
            $(document).on('change', '#party_size, #reservation_date', this.handleDatePartyChange);
            
            // Form submission
            $(document).on('submit', '#yrr-booking-form', this.submitBookingForm);
            
            // My Reservations
            $(document).on('submit', '#yrr-search-form', this.searchReservations);
            
            // Form validation helpers
            $(document).on('blur', '.yrr-form-control', this.validateField);
            $(document).on('input', '.yrr-form-control', this.clearFieldError);
        },

        /**
         * Initialize date pickers
         */
        initDatePickers: function() {
            var today = new Date().toISOString().split('T')[0];
            var maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + 30);
            var maxDateStr = maxDate.toISOString().split('T')[0];
            
            $('#reservation_date').attr('min', today);
            $('#reservation_date').attr('max', maxDateStr);
        },

        /**
         * Load initial form data
         */
        loadInitialData: function() {
            // Set location from form
            var locationId = $('.yrr-public-booking-form').data('location-id');
            if (locationId) {
                this.formData.location_id = locationId;
            }
        },

        /**
         * Navigate to next step
         */
        nextStep: function(step) {
            if (!this.validateCurrentStep()) {
                return false;
            }
            
            this.saveCurrentStepData();
            this.showStep(step);
            
            // Special handling for specific steps
            if (step === 2) {
                this.loadTimeSlots();
            }
        },

        /**
         * Navigate to previous step
         */
        prevStep: function(step) {
            this.showStep(step);
        },

        /**
         * Show specific step
         */
        showStep: function(step) {
            $('.yrr-form-step').removeClass('yrr-step-active');
            $('.yrr-form-step[data-step="' + step + '"]').addClass('yrr-step-active');
            
            this.formData.step = step;
            
            // Scroll to top of form
            $('.yrr-public-booking-form')[0].scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        },

        /**
         * Validate current step
         */
        validateCurrentStep: function() {
            var step = this.formData.step;
            var isValid = true;

            switch (step) {
                case 1:
                    if (!$('#party_size').val()) {
                        this.showFieldError('#party_size', 'Please select party size');
                        isValid = false;
                    }
                    if (!$('#reservation_date').val()) {
                        this.showFieldError('#reservation_date', 'Please select date');
                        isValid = false;
                    }
                    break;
                    
                case 2:
                    if (!$('#reservation_time').val()) {
                        this.showNotification('Please select a time slot', 'error');
                        isValid = false;
                    }
                    break;
                    
                case 3:
                    var requiredFields = ['#customer_name', '#customer_email', '#customer_phone'];
                    requiredFields.forEach(function(field) {
                        if (!$(field).val().trim()) {
                            YRR_Public.showFieldError(field, 'This field is required');
                            isValid = false;
                        }
                    });
                    
                    // Validate email format
                    var email = $('#customer_email').val().trim();
                    if (email && !YRR_Public.isValidEmail(email)) {
                        YRR_Public.showFieldError('#customer_email', 'Please enter a valid email address');
                        isValid = false;
                    }
                    break;
            }

            return isValid;
        },

        /**
         * Save current step data
         */
        saveCurrentStepData: function() {
            var step = this.formData.step;
            
            switch (step) {
                case 1:
                    this.formData.party_size = $('#party_size').val();
                    this.formData.reservation_date = $('#reservation_date').val();
                    break;
                    
                case 2:
                    this.formData.reservation_time = $('#reservation_time').val();
                    break;
                    
                case 3:
                    this.formData.customer_name = $('#customer_name').val().trim();
                    this.formData.customer_email = $('#customer_email').val().trim();
                    this.formData.customer_phone = $('#customer_phone').val().trim();
                    this.formData.special_requests = $('#special_requests').val().trim();
                    break;
            }
        },

        /**
         * Handle date/party size change
         */
        handleDatePartyChange: function() {
            // Clear selected time when date or party size changes
            $('#reservation_time').val('');
            $('.yrr-time-slot').removeClass('selected');
            
            // If both date and party size are selected, load time slots
            if ($('#party_size').val() && $('#reservation_date').val()) {
                YRR_Public.loadTimeSlots();
            }
        },

        /**
         * Load available time slots via AJAX
         */
        loadTimeSlots: function() {
            var date = $('#reservation_date').val();
            var partySize = $('#party_size').val();
            var locationId = this.formData.location_id;

            if (!date || !partySize) {
                $('#yrr-time-slots-container').html('<div class="yrr-loading-message">Please select date and party size first...</div>');
                return;
            }

            // Show loading
            $('#yrr-time-slots-container').html('<div class="yrr-loading-message">Loading available times...</div>');

            $.ajax({
                url: yrr_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'yrr_public_get_slots',
                    date: date,
                    party_size: partySize,
                    location_id: locationId,
                    nonce: yrr_public.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        YRR_Public.renderTimeSlots(response.data);
                    } else {
                        $('#yrr-time-slots-container').html('<div class="yrr-loading-message">No available time slots for the selected date</div>');
                    }
                },
                error: function() {
                    $('#yrr-time-slots-container').html('<div class="yrr-loading-message">Error loading time slots. Please try again.</div>');
                }
            });
        },

        /**
         * Render time slots
         */
        renderTimeSlots: function(slots) {
            var html = '';
            
            slots.forEach(function(slot) {
                var className = 'yrr-time-slot';
                if (!slot.available) {
                    className += ' unavailable';
                }
                
                html += '<div class="' + className + '" data-time="' + slot.time + '">';
                html += slot.display;
                html += '</div>';
            });
            
            $('#yrr-time-slots-container').html(html);
        },

        /**
         * Select time slot
         */
        selectTimeSlot: function(e) {
            if ($(this).hasClass('unavailable')) {
                return;
            }
            
            $('.yrr-time-slot').removeClass('selected');
            $(this).addClass('selected');
            
            var time = $(this).data('time');
            $('#reservation_time').val(time);
        },

        /**
         * Submit booking form
         */
        submitBookingForm: function(e) {
            e.preventDefault();
            
            if (!YRR_Public.validateCurrentStep()) {
                return false;
            }
            
            YRR_Public.saveCurrentStepData();
            
            // Show loading
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.text();
            
            $form.addClass('yrr-loading');
            $button.text('Creating Reservation...').prop('disabled', true);
            
            // Prepare form data
            var formData = $.extend({}, YRR_Public.formData, {
                action: 'yrr_public_create_reservation',
                nonce: yrr_public.nonce
            });
            
            $.ajax({
                url: yrr_public.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        YRR_Public.showNotification('Reservation created successfully! Confirmation code: ' + response.data.reservation_code, 'success');
                        $form[0].reset();
                        YRR_Public.showStep(1);
                    } else {
                        YRR_Public.showNotification(response.data || 'Error creating reservation', 'error');
                    }
                },
                error: function() {
                    YRR_Public.showNotification('Error creating reservation. Please try again.', 'error');
                },
                complete: function() {
                    $form.removeClass('yrr-loading');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Search reservations
         */
        searchReservations: function(e) {
            e.preventDefault();
            
            var email = $('#search_email').val().trim();
            var code = $('#search_code').val().trim();
            
            if (!email) {
                YRR_Public.showNotification('Please enter your email address', 'error');
                return;
            }
            
            var $button = $(this).find('button[type="submit"]');
            var originalText = $button.text();
            $button.text('Searching...').prop('disabled', true);
            
            $.ajax({
                url: yrr_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'yrr_public_get_my_reservations',
                    email: email,
                    code: code,
                    nonce: yrr_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        YRR_Public.renderReservations(response.data.reservations);
                    } else {
                        YRR_Public.showNotification(response.data || 'Error loading reservations', 'error');
                    }
                },
                error: function() {
                    YRR_Public.showNotification('Error loading reservations. Please try again.', 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        /**
         * Render reservations list
         */
        renderReservations: function(reservations) {
            var $list = $('#yrr-reservations-list');
            
            if (!reservations || reservations.length === 0) {
                $list.html('<div class="yrr-empty-message">No reservations found for this email address.</div>');
                return;
            }
            
            var html = '';
            reservations.forEach(function(reservation) {
                var date = new Date(reservation.reservation_date);
                var dateStr = date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
                var timeStr = YRR_Public.formatTime(reservation.reservation_time);
                
                html += '<div class="yrr-reservation-item">';
                html += '<div class="yrr-reservation-header">';
                html += '<div class="yrr-reservation-code">' + reservation.reservation_code + '</div>';
                html += '<div class="yrr-reservation-status ' + reservation.status + '">' + reservation.status + '</div>';
                html += '</div>';
                html += '<div class="yrr-reservation-details">';
                html += '<div><strong>Date:</strong> ' + dateStr + '</div>';
                html += '<div><strong>Time:</strong> ' + timeStr + '</div>';
                html += '<div><strong>Party:</strong> ' + reservation.party_size + ' guests</div>';
                if (reservation.table_number) {
                    html += '<div><strong>Table:</strong> ' + reservation.table_number + '</div>';
                }
                html += '</div>';
                html += '</div>';
            });
            
            $list.html(html);
        },

        /**
         * Utility Functions
         */
        
        /**
         * Format time for display
         */
        formatTime: function(time) {
            if (!time) return '';
            
            var parts = time.split(':');
            var hours = parseInt(parts[0]);
            var minutes = parts[1];
            var ampm = hours >= 12 ? 'PM' : 'AM';
            
            hours = hours % 12;
            hours = hours ? hours : 12;
            
            return hours + ':' + minutes + ' ' + ampm;
        },

        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        /**
         * Show field error
         */
        showFieldError: function(field, message) {
            $(field).addClass('error').css('border-color', '#e74c3c');
            
            // Remove existing error message
            $(field).next('.error-message').remove();
            
            // Add error message
            $('<div class="error-message" style="color: #e74c3c; font-size: 14px; margin-top: 5px;">' + message + '</div>')
                .insertAfter(field);
        },

        /**
         * Clear field error
         */
        clearFieldError: function() {
            $(this).removeClass('error').css('border-color', '');
            $(this).next('.error-message').remove();
        },

        /**
         * Validate individual field
         */
        validateField: function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            // Clear previous errors
            $field.removeClass('error').next('.error-message').remove();
            
            // Required field validation
            if ($field.prop('required') && !value) {
                YRR_Public.showFieldError($field, 'This field is required');
                return false;
            }
            
            // Email validation
            if ($field.attr('type') === 'email' && value && !YRR_Public.isValidEmail(value)) {
                YRR_Public.showFieldError($field, 'Please enter a valid email address');
                return false;
            }
            
            return true;
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            // Remove existing notifications
            $('.yrr-notification').remove();
            
            var notification = $('<div class="yrr-notification yrr-notification-' + type + '">' + message + '</div>');
            notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '15px 20px',
                borderRadius: '6px',
                color: '#fff',
                fontWeight: '500',
                zIndex: 100001,
                display: 'none'
            });
            
            // Set colors based on type
            var colors = {
                success: '#2ecc71',
                error: '#e74c3c',
                warning: '#f39c12',
                info: '#3498db'
            };
            
            notification.css('background', colors[type] || colors.info);
            
            $('body').append(notification);
            
            notification.fadeIn(300).delay(4000).fadeOut(300, function() {
                $(this).remove();
            });
        }
    };

})(jQuery);
