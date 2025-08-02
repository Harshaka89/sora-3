/**
 * Yenolx Restaurant Reservation System v1.6 - Admin JavaScript
 * Handles all interactive functionality for the admin interface
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        YRR_Admin.init();
    });

    /**
     * Main Admin JavaScript Object
     */
    window.YRR_Admin = {
        
        /**
         * Initialize all admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initModals();
            this.initDatePickers();
            this.initTooltips();
            this.initDashboardRefresh();
            this.initFormValidation();
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            // Modal triggers
            $(document).on('click', '.yrr-btn-create-reservation', this.openReservationModal);
            $(document).on('click', '.yrr-btn-edit-reservation', this.editReservation);
            $(document).on('click', '.yrr-modal-close', this.closeModal);
            
            // Form submissions
            $(document).on('submit', '.yrr-reservation-form', this.handleReservationForm);
            
            // Dynamic form updates
            $(document).on('change', '#reservation_date, #party_size, #location_id', this.loadAvailableSlots);
            $(document).on('change', '#reservation_time', this.loadAvailableTables);
            $(document).on('input', '#original_price', this.updateFinalPrice);
            
            // Confirmation dialogs
            $(document).on('click', '.yrr-btn-cancel', this.confirmAction);
            $(document).on('click', '[href*="action=delete"]', this.confirmDelete);
            
            // Stats card animations
            $(document).on('mouseenter', '.yrr-stat-card', this.animateStatCard);
            
            // Location filter
            $(document).on('change', '#location-filter', this.handleLocationFilter);
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts);
        },

        /**
         * Initialize modal functionality
         */
        initModals: function() {
            // Close modal when clicking outside
            $(document).on('click', '.yrr-modal', function(e) {
                if ($(e.target).hasClass('yrr-modal')) {
                    YRR_Admin.closeModal();
                }
            });
            
            // Prevent modal content clicks from closing modal
            $(document).on('click', '.yrr-modal-content', function(e) {
                e.stopPropagation();
            });
            
            // ESC key closes modal
            $(document).on('keyup', function(e) {
                if (e.keyCode === 27) { // ESC key
                    YRR_Admin.closeModal();
                }
            });
        },

        /**
         * Initialize date pickers
         */
        initDatePickers: function() {
            // Check if jQuery UI datepicker is available
            if ($.fn.datepicker) {
                $('#reservation_date').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0,
                    maxDate: '+1y',
                    showAnim: 'slideDown',
                    changeMonth: true,
                    changeYear: true,
                    beforeShowDay: function(date) {
                        // You can add logic here to disable specific dates
                        return [true, ''];
                    }
                });
            } else {
                // Fallback to HTML5 date input
                $('#reservation_date').attr('type', 'date');
                var today = new Date().toISOString().split('T')[0];
                $('#reservation_date').attr('min', today);
            }
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                $(this).attr('title', $(this).data('tooltip'));
            });
        },

        /**
         * Initialize dashboard auto-refresh
         */
        initDashboardRefresh: function() {
            // Refresh dashboard every 5 minutes
            if ($('.yrr-admin-wrap').length && window.location.href.indexOf('yrr-dashboard') > -1) {
                setInterval(function() {
                    YRR_Admin.refreshDashboardStats();
                }, 300000); // 5 minutes
            }
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            $('.yrr-reservation-form').each(function() {
                $(this).on('submit', function(e) {
                    return YRR_Admin.validateReservationForm($(this));
                });
            });
        },

        /**
         * Open reservation modal
         */
        openReservationModal: function(e) {
            e.preventDefault();
            $('#yrr-create-reservation-modal').fadeIn(300);
            $('#customer_name').focus();
            
            // Reset form
            $('.yrr-reservation-form')[0].reset();
            $('#reservation_time').html('<option value="">' + yrr_admin.strings.loading + '</option>');
            $('#table_id').html('<option value="">Auto-assign</option>');
        },

        /**
         * Edit reservation
         */
        editReservation: function(e) {
            e.preventDefault();
            var reservationId = $(this).data('id');
            
            // In a full implementation, you would load the reservation data via AJAX
            // For now, we'll just open the modal
            YRR_Admin.openReservationModal(e);
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.yrr-modal').fadeOut(300);
        },

        /**
         * Handle reservation form submission
         */
        handleReservationForm: function(e) {
            var $form = $(this);
            
            if (!YRR_Admin.validateReservationForm($form)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            $form.addClass('yrr-loading');
            $form.find('button[type="submit"]').prop('disabled', true);
            
            // Form validation passed - let it submit normally
            // The loading state will be cleared on page reload
        },

        /**
         * Validate reservation form
         */
        validateReservationForm: function($form) {
            var isValid = true;
            var errors = [];
            
            // Required field validation
            $form.find('input[required], select[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass('error');
                    errors.push($(this).prev('label').text() + ' is required.');
                } else {
                    $(this).removeClass('error');
                }
            });
            
            // Email validation
            var email = $form.find('#customer_email').val();
            if (email && !YRR_Admin.isValidEmail(email)) {
                isValid = false;
                $form.find('#customer_email').addClass('error');
                errors.push('Please enter a valid email address.');
            }
            
            // Phone validation
            var phone = $form.find('#customer_phone').val();
            if (phone && phone.length < 10) {
                isValid = false;
                $form.find('#customer_phone').addClass('error');
                errors.push('Please enter a valid phone number.');
            }
            
            // Show errors if any
            if (!isValid) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
            }
            
            return isValid;
        },

        /**
         * Load available time slots
         */
        loadAvailableSlots: function() {
            var date = $('#reservation_date').val();
            var partySize = $('#party_size').val();
            var locationId = $('#location_id').val() || 1;
            
            if (!date || !partySize) {
                return;
            }
            
            var $timeSelect = $('#reservation_time');
            $timeSelect.html('<option value="">' + yrr_admin.strings.loading + '</option>');
            
            $.ajax({
                url: yrr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'yrr_get_available_slots',
                    date: date,
                    party_size: partySize,
                    location_id: locationId,
                    nonce: yrr_admin.nonce
                },
                success: function(response) {
                    var options = '<option value="">Select time...</option>';
                    
                    if (response.success && response.data.length > 0) {
                        response.data.forEach(function(slot) {
                            options += '<option value="' + slot + '">' + YRR_Admin.formatTime(slot) + '</option>';
                        });
                    } else {
                        options = '<option value="">' + yrr_admin.strings.no_slots + '</option>';
                    }
                    
                    $timeSelect.html(options);
                },
                error: function() {
                    $timeSelect.html('<option value="">Error loading slots</option>');
                }
            });
        },

        /**
         * Load available tables
         */
        loadAvailableTables: function() {
            var date = $('#reservation_date').val();
            var time = $('#reservation_time').val();
            var partySize = $('#party_size').val();
            var locationId = $('#location_id').val() || 1;
            
            if (!date || !time || !partySize) {
                return;
            }
            
            $.ajax({
                url: yrr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'yrr_get_available_tables',
                    date: date,
                    time: time,
                    party_size: partySize,
                    location_id: locationId,
                    nonce: yrr_admin.nonce
                },
                success: function(response) {
                    var options = '<option value="">Auto-assign</option>';
                    
                    if (response.success) {
                        response.data.forEach(function(table) {
                            options += '<option value="' + table.id + '">' + 
                                      table.table_number + ' (' + table.capacity + ' seats)</option>';
                        });
                    }
                    
                    $('#table_id').html(options);
                }
            });
        },

        /**
         * Update final price when original price changes
         */
        updateFinalPrice: function() {
            var originalPrice = parseFloat($(this).val()) || 0;
            $('#final_price').val(originalPrice.toFixed(2));
        },

        /**
         * Confirm action with dialog
         */
        confirmAction: function(e) {
            var message = $(this).data('confirm') || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        },

        /**
         * Confirm delete action
         */
        confirmDelete: function(e) {
            if (!confirm(yrr_admin.strings.confirm_delete)) {
                e.preventDefault();
                return false;
            }
        },

        /**
         * Animate stat card on hover
         */
        animateStatCard: function() {
            $(this).find('.yrr-stat-content h3').addClass('animate-bounce');
            setTimeout(function() {
                $('.yrr-stat-content h3').removeClass('animate-bounce');
            }, 600);
        },

        /**
         * Handle location filter change
         */
        handleLocationFilter: function() {
            var locationId = $(this).val();
            var currentUrl = window.location.href;
            var newUrl = YRR_Admin.updateURLParameter(currentUrl, 'location_id', locationId);
            window.location.href = newUrl;
        },

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcuts: function(e) {
            // Ctrl/Cmd + N = New Reservation
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 78) {
                e.preventDefault();
                $('.yrr-btn-create-reservation').first().click();
            }
            
            // Ctrl/Cmd + S = Save (if in modal)
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 83 && $('.yrr-modal:visible').length) {
                e.preventDefault();
                $('.yrr-modal:visible form').submit();
            }
        },

        /**
         * Refresh dashboard statistics
         */
        refreshDashboardStats: function() {
            $.ajax({
                url: yrr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'yrr_get_dashboard_stats',
                    nonce: yrr_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        YRR_Admin.updateStatsDisplay(response.data);
                    }
                }
            });
        },

        /**
         * Update stats display
         */
        updateStatsDisplay: function(stats) {
            $('.yrr-stat-total h3').text(YRR_Admin.formatNumber(stats.total || 0));
            $('.yrr-stat-today h3').text(YRR_Admin.formatNumber(stats.today || 0));
            $('.yrr-stat-pending h3').text(YRR_Admin.formatNumber(stats.pending || 0));
            $('.yrr-stat-guests h3').text(YRR_Admin.formatNumber(stats.today_guests || 0));
            $('.yrr-stat-week h3').text(YRR_Admin.formatNumber(stats.this_week || 0));
            
            // Update revenue with currency symbol
            var revenue = stats.revenue || 0;
            $('.yrr-stat-revenue h3').text('$' + YRR_Admin.formatNumber(revenue, 2));
        },

        /**
         * Utility Functions
         */
        
        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

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
            hours = hours ? hours : 12; // 0 should be 12
            
            return hours + ':' + minutes + ' ' + ampm;
        },

        /**
         * Format number with commas
         */
        formatNumber: function(num, decimals) {
            decimals = decimals || 0;
            return parseFloat(num).toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        },

        /**
         * Update URL parameter
         */
        updateURLParameter: function(url, param, paramVal) {
            var newAdditionalURL = "";
            var tempArray = url.split("?");
            var baseURL = tempArray[0];
            var additionalURL = tempArray[1];
            var temp = "";
            
            if (additionalURL) {
                tempArray = additionalURL.split("&");
                for (var i = 0; i < tempArray.length; i++) {
                    if (tempArray[i].split('=')[0] != param) {
                        newAdditionalURL += temp + tempArray[i];
                        temp = "&";
                    }
                }
            }
            
            var rows_txt = temp + "" + param + "=" + paramVal;
            return baseURL + "?" + newAdditionalURL + rows_txt;
        },

        /**
         * Show loading overlay
         */
        showLoading: function(element) {
            $(element).addClass('yrr-loading');
        },

        /**
         * Hide loading overlay
         */
        hideLoading: function(element) {
            $(element).removeClass('yrr-loading');
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'info';
            var notification = $('<div class="yrr-notification yrr-notification-' + type + '">' + message + '</div>');
            
            $('body').append(notification);
            
            notification.fadeIn(300).delay(3000).fadeOut(300, function() {
                $(this).remove();
            });
        }
    };

    // CSS for animations and additional styling
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .animate-bounce {
                animation: bounce 0.6s ease-in-out;
            }
            
            @keyframes bounce {
                0%, 20%, 60%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-10px);
                }
                80% {
                    transform: translateY(-5px);
                }
            }
            
            .yrr-form-field input.error,
            .yrr-form-field select.error {
                border-color: #e74c3c !important;
                box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
            }
            
            .yrr-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 6px;
                color: #fff;
                font-weight: 500;
                z-index: 100001;
                display: none;
            }
            
            .yrr-notification-success { background: #2ecc71; }
            .yrr-notification-error { background: #e74c3c; }
            .yrr-notification-warning { background: #f39c12; }
            .yrr-notification-info { background: #3498db; }
        `)
        .appendTo('head');

})(jQuery);
