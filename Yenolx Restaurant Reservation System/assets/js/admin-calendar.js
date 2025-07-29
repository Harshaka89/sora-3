/*!
 * Yenolx Restaurant Reservation System v1.6
 * Calendar Admin Interface JavaScript
 * Touch-Friendly Interactive Calendar
 */

(function($) {
    'use strict';

    // Main Calendar Object
    window.YRR_Calendar = {
        // Touch tracking
        touchStartX: 0,
        touchStartY: 0,
        
        // Configuration
        config: {
            enableSwipeGestures: true,
            enableHapticFeedback: true,
            autoRefreshInterval: 30000, // 30 seconds
            touchThreshold: 50,
            animationDuration: 300
        },

        /**
         * Initialize Calendar
         */
        init: function() {
            console.log('YRR Calendar: Initializing...');
            
            this.bindEvents();
            this.enhanceAccessibility();
            this.updateCurrentTime();
            
            if (this.config.enableSwipeGestures) {
                this.initSwipeGestures();
            }
            
            this.startAutoRefresh();
            
            console.log('YRR Calendar: Initialized successfully');
        },

        /**
         * Bind Event Handlers
         */
        bindEvents: function() {
            // Empty slot creation
            $(document).on('click touchend', '.yrr-empty-slot', this.handleEmptySlotClick.bind(this));
            
            // Reservation details
            $(document).on('click touchend', '.yrr-reservation-block', this.handleReservationClick.bind(this));
            
            // Modal controls
            $('.yrr-modal-close, .yrr-modal-overlay').on('click', this.closeModal.bind(this));
            $('.yrr-modal-content').on('click', function(e) { e.stopPropagation(); });
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeydown.bind(this));
            
            // Window resize
            $(window).on('resize', this.debounce(this.handleResize.bind(this), 250));
        },

        /**
         * Handle Empty Slot Click
         */
        handleEmptySlotClick: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $slot = $(e.currentTarget);
            const date = $slot.closest('.yrr-day-column').data('date');
            const time = $slot.closest('.yrr-time-slot-container').data('time');
            
            // Visual feedback
            this.addInteractionFeedback($slot, 'yrr-slot-active');
            
            // Haptic feedback
            this.triggerHapticFeedback(50);
            
            // Create reservation
            this.createReservation(date, time);
        },

        /**
         * Handle Reservation Click
         */
        handleReservationClick: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $block = $(e.currentTarget);
            const reservationId = $block.data('reservation-id');
            
            // Visual feedback
            this.addInteractionFeedback($block, 'yrr-reservation-active');
            
            // Haptic feedback
            this.triggerHapticFeedback(30);
            
            // Show details
            this.showReservationDetails(reservationId);
        },

        /**
         * Create New Reservation
         */
        createReservation: function(date, time) {
            console.log('Creating reservation for:', date, time);
            
            // Show loading
            this.showLoading();
            
            // Check if modal exists
            const $modal = $('#yrr-new-reservation-modal');
            if ($modal.length) {
                // Pre-fill modal
                $('#modal_reservation_date').val(date);
                $('#modal_reservation_time').val(time.substring(0, 5));
                
                // Show modal
                setTimeout(() => {
                    this.hideLoading();
                    this.showModal($modal);
                    
                    // Focus first field
                    setTimeout(() => {
                        $('#modal_customer_name').focus();
                    }, this.config.animationDuration);
                }, 200);
            } else {
                // Fallback: Navigate to add page
                this.hideLoading();
                const addUrl = this.buildAddReservationUrl(date, time);
                window.location.href = addUrl;
            }
        },

        /**
         * Show Reservation Details
         */
        showReservationDetails: function(reservationId) {
            console.log('Showing details for reservation:', reservationId);
            
            const $modal = $('#yrr-reservation-details-modal');
            const $content = $('#yrr-reservation-details-content');
            
            // Show loading content
            $content.html(this.getLoadingHTML());
            this.showModal($modal);
            
            // Simulate loading (replace with actual AJAX in production)
            setTimeout(() => {
                $content.html(this.getReservationDetailsHTML(reservationId));
            }, 800);
        },

        /**
         * Initialize Swipe Gestures
         */
        initSwipeGestures: function() {
            const $calendar = $('.yrr-calendar-container');
            
            $calendar.on('touchstart', (e) => {
                this.touchStartX = e.originalEvent.touches[0].clientX;
                this.touchStartY = e.originalEvent.touches[0].clientY;
            });
            
            $calendar.on('touchend', (e) => {
                if (!this.touchStartX || !this.touchStartY) return;
                
                const touchEndX = e.originalEvent.changedTouches[0].clientX;
                const touchEndY = e.originalEvent.changedTouches[0].clientY;
                
                const diffX = this.touchStartX - touchEndX;
                const diffY = this.touchStartY - touchEndY;
                
                // Process horizontal swipes
                if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > this.config.touchThreshold) {
                    if (diffX > 0) {
                        // Swipe left - next week
                        this.navigateWeek('next');
                    } else {
                        // Swipe right - previous week
                        this.navigateWeek('prev');
                    }
                    
                    this.triggerHapticFeedback(30);
                }
                
                this.touchStartX = 0;
                this.touchStartY = 0;
            });
        },

        /**
         * Navigate Week
         */
        navigateWeek: function(direction) {
            const $button = direction === 'next' 
                ? $('.yrr-calendar-nav a:last-child')
                : $('.yrr-calendar-nav a:first-child');
            
            if ($button.length) {
                $button[0].click();
            }
        },

        /**
         * Enhance Accessibility
         */
        enhanceAccessibility: function() {
            // Add ARIA labels
            $('.yrr-empty-slot').attr({
                'aria-label': 'Create new reservation',
                'tabindex': '0',
                'role': 'button'
            });
            
            $('.yrr-reservation-block').attr({
                'aria-label': 'View reservation details',
                'tabindex': '0',
                'role': 'button'
            });
            
            // Keyboard navigation
            $(document).on('keydown', '.yrr-empty-slot, .yrr-reservation-block', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(e.target).click();
                }
            });
        },

        /**
         * Update Current Time Indicator
         */
        updateCurrentTime: function() {
            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            
            if (currentHour >= 9 && currentHour <= 23) {
                const $todayColumn = $('.yrr-today');
                if ($todayColumn.length) {
                    const $timeSlot = $todayColumn.find('.yrr-time-slot-container').eq(currentHour - 9);
                    const percentOfHour = (currentMinute / 60) * 100;
                    
                    // Remove existing indicators
                    $('.yrr-current-time-indicator').remove();
                    
                    // Add new indicator
                    $timeSlot.append(this.getCurrentTimeIndicatorHTML(percentOfHour));
                }
            }
            
            // Schedule next update
            setTimeout(() => this.updateCurrentTime(), 60000);
        },

        /**
         * Handle Keyboard Shortcuts
         */
        handleKeydown: function(e) {
            // Escape key - close modals
            if (e.key === 'Escape') {
                this.closeModal();
                return;
            }
            
            // Week navigation with Ctrl/Cmd + Arrow keys
            if (e.ctrlKey || e.metaKey) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.navigateWeek('prev');
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.navigateWeek('next');
                }
            }
        },

        /**
         * Handle Window Resize
         */
        handleResize: function() {
            // Adjust calendar layout if needed
            console.log('Window resized, adjusting calendar layout');
        },

        /**
         * Show Modal
         */
        showModal: function($modal) {
            $modal.fadeIn(this.config.animationDuration);
            $('body').addClass('modal-open');
        },

        /**
         * Close Modal
         */
        closeModal: function() {
            $('.yrr-modal').fadeOut(200);
            $('body').removeClass('modal-open');
        },

        /**
         * Show Loading State
         */
        showLoading: function() {
            $('body').addClass('yrr-calendar-loading');
        },

        /**
         * Hide Loading State
         */
        hideLoading: function() {
            $('body').removeClass('yrr-calendar-loading');
        },

        /**
         * Add Interactive Feedback
         */
        addInteractionFeedback: function($element, className) {
            $element.addClass(className);
            setTimeout(() => {
                $element.removeClass(className);
            }, 200);
        },

        /**
         * Trigger Haptic Feedback
         */
        triggerHapticFeedback: function(duration) {
            if (this.config.enableHapticFeedback && navigator.vibrate) {
                navigator.vibrate(duration);
            }
        },

        /**
         * Start Auto Refresh
         */
        startAutoRefresh: function() {
            if (this.config.autoRefreshInterval > 0) {
                setInterval(() => {
                    if (!$('.yrr-modal:visible').length) {
                        this.refreshCalendar();
                    }
                }, this.config.autoRefreshInterval);
            }
        },

        /**
         * Refresh Calendar Data
         */
        refreshCalendar: function() {
            console.log('Auto-refreshing calendar data...');
            // Implementation would depend on your specific needs
        },

        /**
         * Utility: Debounce Function
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Build Add Reservation URL
         */
        buildAddReservationUrl: function(date, time) {
            const baseUrl = typeof yrr_admin !== 'undefined' && yrr_admin.add_url 
                ? yrr_admin.add_url 
                : '/wp-admin/admin.php?page=yrr-reservations&action=add';
            
            return baseUrl + '&date=' + encodeURIComponent(date) + '&time=' + encodeURIComponent(time.substring(0, 5));
        },

        /**
         * Get Loading HTML
         */
        getLoadingHTML: function() {
            return `
                <div class="yrr-modern-loading" style="text-align: center; padding: 3rem 2rem;">
                    <div class="yrr-loading-spinner"></div>
                    <p>Loading reservation details...</p>
                </div>
            `;
        },

        /**
         * Get Reservation Details HTML
         */
        getReservationDetailsHTML: function(reservationId) {
            return `
                <div class="yrr-reservation-details-modern" style="padding: 1rem;">
                    <div class="yrr-detail-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--yrr-border);">
                        <h3>Reservation #${reservationId}</h3>
                        <span class="yrr-status-badge yrr-status-confirmed" style="padding: 4px 8px; background: var(--yrr-success); color: white; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Confirmed</span>
                    </div>
                    <div class="yrr-detail-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <div class="yrr-detail-item">
                            <label style="font-weight: 600; color: var(--yrr-text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Customer:</label>
                            <span style="font-weight: 600; color: var(--yrr-text-primary);">Loading...</span>
                        </div>
                        <div class="yrr-detail-item">
                            <label style="font-weight: 600; color: var(--yrr-text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Party Size:</label>
                            <span style="font-weight: 600; color: var(--yrr-text-primary);">Loading...</span>
                        </div>
                        <div class="yrr-detail-item">
                            <label style="font-weight: 600; color: var(--yrr-text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Date & Time:</label>
                            <span style="font-weight: 600; color: var(--yrr-text-primary);">Loading...</span>
                        </div>
                        <div class="yrr-detail-item">
                            <label style="font-weight: 600; color: var(--yrr-text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem; display: block;">Table:</label>
                            <span style="font-weight: 600; color: var(--yrr-text-primary);">Loading...</span>
                        </div>
                    </div>
                    <div class="yrr-detail-actions" style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button class="button button-primary" onclick="window.location.href='/wp-admin/admin.php?page=yrr-reservations'">
                            View All Reservations
                        </button>
                    </div>
                </div>
            `;
        },

        /**
         * Get Current Time Indicator HTML
         */
        getCurrentTimeIndicatorHTML: function(percentOfHour) {
            return `
                <div class="yrr-current-time-indicator" 
                     style="position: absolute; top: ${percentOfHour}%; left: 0; right: 0; height: 2px; 
                            background: linear-gradient(90deg, #ef4444, #f59e0b); 
                            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5); z-index: 10;
                            animation: pulse 2s infinite;">
                    <div style="position: absolute; left: -4px; top: -4px; width: 10px; height: 10px; 
                                background: #ef4444; border-radius: 50%; box-shadow: 0 0 10px rgba(239, 68, 68, 0.8);">
                    </div>
                </div>
            `;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        YRR_Calendar.init();
    });

})(jQuery);
