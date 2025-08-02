<?php
/**
 * Public Reservation Controller - Handles customer-facing booking functionality
 * Provides shortcodes, AJAX endpoints, and public reservation management
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

class YRR_Public_Controller {
    
    /**
     * Initialize public controller
     */
    public function __construct() {
        // Always load assets on frontend - simplified approach
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 999);
        }
        
        // Register shortcodes
        add_shortcode('yrr_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('yrr_my_reservations', array($this, 'my_reservations_shortcode'));
        
        // AJAX hooks for both logged in and non-logged in users
        add_action('wp_ajax_yrr_public_get_slots', array($this, 'ajax_get_available_slots'));
        add_action('wp_ajax_nopriv_yrr_public_get_slots', array($this, 'ajax_get_available_slots'));
        
        add_action('wp_ajax_yrr_public_validate_coupon', array($this, 'ajax_validate_coupon'));
        add_action('wp_ajax_nopriv_yrr_public_validate_coupon', array($this, 'ajax_validate_coupon'));
        
        add_action('wp_ajax_yrr_public_create_reservation', array($this, 'ajax_create_reservation'));
        add_action('wp_ajax_nopriv_yrr_public_create_reservation', array($this, 'ajax_create_reservation'));
        
        add_action('wp_ajax_yrr_public_get_my_reservations', array($this, 'ajax_get_my_reservations'));
        add_action('wp_ajax_nopriv_yrr_public_get_my_reservations', array($this, 'ajax_get_my_reservations'));
        
        // Form submission handler
        add_action('wp_loaded', array($this, 'handle_form_submission'));
    }
    
    /**
     * Enqueue public scripts and styles
     */
  /**
 * Enqueue public scripts and styles
 */
/**
 * Enqueue public scripts and styles
 */
public function enqueue_scripts() {
    // Don't load on admin pages
    if (is_admin()) {
        return;
    }
    
    // Enqueue CSS
    wp_enqueue_style(
        'yrr-public', 
        YRR_PLUGIN_URL . 'assets/public.css', 
        array(), 
        YRR_VERSION
    );
    
    // Enqueue JavaScript  
    wp_enqueue_script(
        'yrr-public', 
        YRR_PLUGIN_URL . 'assets/public.js', 
        array('jquery'), 
        YRR_VERSION,
        true
    );
    
    // Simple localize script - no complex arrays
    wp_localize_script('yrr-public', 'yrr_public', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yrr_public_nonce'),
        'loading' => 'Loading...',
        'select_date' => 'Please select a date',
        'select_time' => 'Please select a time',
        'no_slots' => 'No available time slots',
        'booking_success' => 'Reservation created successfully!',
        'booking_error' => 'Error creating reservation',
        'max_party_size' => 12,
        'currency_symbol' => '$'
    ));
}

    
    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location_id' => 1,
            'show_location_selector' => 'false',
            'theme' => 'default',
            'title' => __('Make a Reservation', 'yrr')
        ), $atts, 'yrr_booking_form');
        
        ob_start();
        
        // Get available locations
        $locations = YRR_Locations_Model::get_all(true);
        $selected_location = YRR_Locations_Model::get_by_id($atts['location_id']);
        
        // Fallback to first location if none found
        if (!$selected_location && !empty($locations)) {
            $selected_location = $locations[0];
        }
        
        // Create a default location object if none exist
        if (!$selected_location) {
            $selected_location = (object) array(
                'id' => 1,
                'name' => 'Main Location'
            );
        }
        
        ?>
        <div class="yrr-public-booking-form" data-location-id="<?php echo esc_attr($selected_location->id); ?>">
            
            <?php if ($atts['title']): ?>
            <h3 class="yrr-form-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <?php if ($atts['show_location_selector'] === 'true' && count($locations) > 1): ?>
            <div class="yrr-form-section">
                <label for="yrr-location-select"><?php _e('Select Location', 'yrr'); ?></label>
                <select id="yrr-location-select" class="yrr-form-control">
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo esc_attr($location->id); ?>" <?php selected($location->id, $selected_location->id); ?>>
                            <?php echo esc_html($location->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <form id="yrr-booking-form" class="yrr-booking-form" method="post">
                <?php wp_nonce_field('yrr_public_booking', 'yrr_booking_nonce'); ?>
                <input type="hidden" name="action" value="yrr_create_public_reservation">
                <input type="hidden" name="location_id" value="<?php echo esc_attr($selected_location->id); ?>">
                
                <!-- Step 1: Party Details -->
                <div class="yrr-form-step yrr-step-active" data-step="1">
                    <h4><?php _e('Party Details', 'yrr'); ?></h4>
                    
                    <div class="yrr-form-row">
                        <div class="yrr-form-field">
                            <label for="party_size"><?php _e('Party Size', 'yrr'); ?> *</label>
                            <select id="party_size" name="party_size" required class="yrr-form-control">
                                <option value=""><?php _e('Select party size...', 'yrr'); ?></option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>">
                                        <?php echo $i; ?> <?php echo $i === 1 ? __('guest', 'yrr') : __('guests', 'yrr'); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="yrr-form-field">
                            <label for="reservation_date"><?php _e('Preferred Date', 'yrr'); ?> *</label>
                            <input type="date" id="reservation_date" name="reservation_date" required 
                                   class="yrr-form-control" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="yrr-form-actions">
                        <button type="button" class="yrr-btn yrr-btn-primary" onclick="YRR_Public.nextStep(2)">
                            <?php _e('Choose Time', 'yrr'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Time Selection -->
                <div class="yrr-form-step" data-step="2">
                    <h4><?php _e('Available Times', 'yrr'); ?></h4>
                    
                    <div id="yrr-time-slots-container" class="yrr-time-slots">
                        <div class="yrr-loading-message">
                            <?php _e('Please select date and party size first...', 'yrr'); ?>
                        </div>
                    </div>
                    
                    <input type="hidden" id="reservation_time" name="reservation_time" required>
                    
                    <div class="yrr-form-actions">
                        <button type="button" class="yrr-btn yrr-btn-secondary" onclick="YRR_Public.prevStep(1)">
                            <?php _e('Back', 'yrr'); ?>
                        </button>
                        <button type="button" class="yrr-btn yrr-btn-primary" onclick="YRR_Public.nextStep(3)">
                            <?php _e('Enter Details', 'yrr'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Step 3: Customer Information -->
                <div class="yrr-form-step" data-step="3">
                    <h4><?php _e('Your Information', 'yrr'); ?></h4>
                    
                    <div class="yrr-form-row">
                        <div class="yrr-form-field">
                            <label for="customer_name"><?php _e('Full Name', 'yrr'); ?> *</label>
                            <input type="text" id="customer_name" name="customer_name" required class="yrr-form-control">
                        </div>
                        
                        <div class="yrr-form-field">
                            <label for="customer_email"><?php _e('Email Address', 'yrr'); ?> *</label>
                            <input type="email" id="customer_email" name="customer_email" required class="yrr-form-control">
                        </div>
                    </div>
                    
                    <div class="yrr-form-row">
                        <div class="yrr-form-field">
                            <label for="customer_phone"><?php _e('Phone Number', 'yrr'); ?> *</label>
                            <input type="tel" id="customer_phone" name="customer_phone" required class="yrr-form-control">
                        </div>
                    </div>
                    
                    <div class="yrr-form-field">
                        <label for="special_requests"><?php _e('Special Requests (Optional)', 'yrr'); ?></label>
                        <textarea id="special_requests" name="special_requests" rows="3" class="yrr-form-control"></textarea>
                    </div>
                    
                    <div class="yrr-form-actions">
                        <button type="button" class="yrr-btn yrr-btn-secondary" onclick="YRR_Public.prevStep(2)">
                            <?php _e('Back', 'yrr'); ?>
                        </button>
                        <button type="submit" class="yrr-btn yrr-btn-success">
                            <?php _e('Complete Reservation', 'yrr'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * My reservations shortcode  
     */
    public function my_reservations_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('My Reservations', 'yrr'),
            'show_search' => 'true'
        ), $atts, 'yrr_my_reservations');
        
        ob_start();
        ?>
        <div class="yrr-my-reservations">
            <?php if ($atts['title']): ?>
            <h3 class="yrr-section-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <?php if ($atts['show_search'] === 'true'): ?>
            <div class="yrr-reservation-search">
                <form id="yrr-search-form" class="yrr-search-form">
                    <div class="yrr-form-row">
                        <div class="yrr-form-field">
                            <label for="search_email"><?php _e('Email Address', 'yrr'); ?></label>
                            <input type="email" id="search_email" name="search_email" required class="yrr-form-control">
                        </div>
                        <div class="yrr-form-field">
                            <label for="search_code"><?php _e('Reservation Code (Optional)', 'yrr'); ?></label>
                            <input type="text" id="search_code" name="search_code" class="yrr-form-control">
                        </div>
                    </div>
                    <div class="yrr-form-actions">
                        <button type="submit" class="yrr-btn yrr-btn-primary">
                            <?php _e('Find My Reservations', 'yrr'); ?>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <div id="yrr-reservations-list" class="yrr-reservations-list">
                <div class="yrr-empty-message">
                    <?php _e('Enter your email address to view your reservations.', 'yrr'); ?>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * AJAX: Get available time slots
     */
    public function ajax_get_available_slots() {
        check_ajax_referer('yrr_public_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $party_size = intval($_POST['party_size']);
        $location_id = intval($_POST['location_id']);
        
        if (!$date || !$party_size) {
            wp_send_json_error(__('Missing required parameters.', 'yrr'));
            return;
        }
        
        // Get available slots using existing admin logic
        $slots = $this->get_available_slots($date, $party_size, $location_id);
        
        wp_send_json_success($slots);
    }
    
    /**
     * Get available slots (reuses admin logic)
     */
    private function get_available_slots($date, $party_size, $location_id = 1) {
        // Get operating hours for this day
        $day_of_week = date('l', strtotime($date));
        $hours = YRR_Hours_Model::get_hours_for_day($day_of_week, $location_id);
        
        if (!$hours || $hours->is_closed) {
            return array();
        }
        
        // Generate time slots
        $slot_duration = 60; // 1 hour slots
        $slots = array();
        
        $current_time = strtotime($hours->open_time);
        $close_time = strtotime($hours->close_time);
        $buffer_hours = 2;
        $last_booking = $close_time - ($buffer_hours * 3600);
        
        // Handle overnight service
        if ($close_time < $current_time) {
            $close_time += 24 * 3600; // Add 24 hours
        }
        
        while ($current_time <= $last_booking) {
            $slot_time = date('H:i:s', $current_time);
            
            // Skip break times
            if ($hours->break_start && $hours->break_end) {
                $break_start = strtotime($hours->break_start);
                $break_end = strtotime($hours->break_end);
                
                if ($current_time >= $break_start && $current_time < $break_end) {
                    $current_time += $slot_duration * 60;
                    continue;
                }
            }
            
            // Check if slot is available
            if ($this->is_slot_available($date, $slot_time, $party_size, $location_id)) {
                $slots[] = array(
                    'time' => $slot_time,
                    'display' => date('g:i A', $current_time),
                    'available' => true
                );
            }
            
            $current_time += $slot_duration * 60;
        }
        
        return $slots;
    }
    
    /**
     * Check if slot is available
     */
    private function is_slot_available($date, $time, $party_size, $location_id) {
        // Get available tables
        $available_tables = YRR_Tables_Model::get_available_by_capacity($party_size, $date, $time);
        
        if (empty($available_tables)) {
            return false;
        }
        
        // Filter by location if specified
        if ($location_id) {
            $available_tables = array_filter($available_tables, function($table) use ($location_id) {
                return $table->location_id == $location_id;
            });
        }
        
        return !empty($available_tables);
    }
        /**
     * AJAX: Validate coupon
     */
    public function ajax_validate_coupon() {
        check_ajax_referer('yrr_public_nonce', 'nonce');
        
        $code = strtoupper(sanitize_text_field($_POST['code']));
        $total = floatval($_POST['total']) ?: 0;
        
        if (!$code) {
            wp_send_json_error(__('Please enter a coupon code.', 'yrr'));
            return;
        }
        
        // Validate coupon using existing model
        $coupon = YRR_Coupons_Model::validate_coupon($code, $total);
        
        if (is_wp_error($coupon)) {
            wp_send_json_error($coupon->get_error_message());
            return;
        }
        
        // Calculate discount
        $discount = YRR_Coupons_Model::calculate_discount($coupon, $total);
        $final_total = max(0, $total - $discount);
        
        wp_send_json_success(array(
            'code' => $code,
            'discount_amount' => $discount,
            'final_total' => $final_total,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value
        ));
    }
    
    /**
     * AJAX: Create reservation
     */
    public function ajax_create_reservation() {
        check_ajax_referer('yrr_public_nonce', 'nonce');
        
        // Validate required fields
        $required_fields = array('customer_name', 'customer_email', 'customer_phone', 'party_size', 'reservation_date', 'reservation_time', 'location_id');
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(sprintf(__('Field %s is required.', 'yrr'), $field));
                return;
            }
        }
        
        // Prepare reservation data
        $data = array(
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'party_size' => intval($_POST['party_size']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'reservation_time' => sanitize_text_field($_POST['reservation_time']),
            'location_id' => intval($_POST['location_id']),
            'special_requests' => sanitize_textarea_field($_POST['special_requests']),
            'original_price' => floatval($_POST['original_price']) ?: 0,
            'discount_amount' => floatval($_POST['discount_amount']) ?: 0,
            'final_price' => floatval($_POST['final_price']) ?: 0,
            'coupon_code' => !empty($_POST['coupon_code']) ? strtoupper(sanitize_text_field($_POST['coupon_code'])) : null,
            'source' => 'public'
        );
        
        // Auto-assign optimal table
        $optimal_table = YRR_Tables_Model::get_optimal_table(
            $data['party_size'], 
            $data['reservation_date'], 
            $data['reservation_time'], 
            $data['location_id']
        );
        
        if ($optimal_table) {
            $data['table_id'] = $optimal_table->id;
        }
        
        // Create reservation
        $reservation_id = YRR_Reservation_Model::create($data);
        
        if (is_wp_error($reservation_id)) {
            wp_send_json_error($reservation_id->get_error_message());
            return;
        }
        
        // Get created reservation for response
        $reservation = YRR_Reservation_Model::get_by_id($reservation_id);
        
        // Send confirmation email
        if (YRR_Settings_Model::get_setting('email_enabled', 1)) {
            $this->send_confirmation_email($reservation);
        }
        
        wp_send_json_success(array(
            'reservation_code' => $reservation->reservation_code,
            'message' => __('Your reservation has been created successfully!', 'yrr')
        ));
    }
    
    /**
     * AJAX: Get customer reservations
     */
    public function ajax_get_my_reservations() {
        check_ajax_referer('yrr_public_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $code = !empty($_POST['code']) ? sanitize_text_field($_POST['code']) : null;
        
        if (!$email) {
            wp_send_json_error(__('Email address is required.', 'yrr'));
            return;
        }
        
        // Get reservations
        $reservations = YRR_Reservation_Model::get_by_customer_email($email, 20);
        
        // Filter by code if provided
        if ($code) {
            $reservations = array_filter($reservations, function($reservation) use ($code) {
                return stripos($reservation->reservation_code, $code) !== false;
            });
        }
        
        wp_send_json_success(array(
            'reservations' => array_values($reservations),
            'count' => count($reservations)
        ));
    }
    
    /**
     * Handle form submission (non-AJAX fallback)
     */
    public function handle_form_submission() {
        if (!isset($_POST['action']) || $_POST['action'] !== 'yrr_create_public_reservation') {
            return;
        }
        
        if (!wp_verify_nonce($_POST['yrr_booking_nonce'], 'yrr_public_booking')) {
            wp_die(__('Security verification failed.', 'yrr'));
        }
        
        // Handle the same as AJAX version but redirect instead of JSON response
        // This is a fallback for users with JavaScript disabled
        $this->ajax_create_reservation();
    }
    
    /**
     * Send confirmation email
     */
    private function send_confirmation_email($reservation) {
        $restaurant_info = YRR_Settings_Model::get_restaurant_info();
        $email_settings = YRR_Settings_Model::get_email_settings();
        
        $subject = 'Reservation Confirmation - ' . $reservation->reservation_code;
        
        $message = sprintf(
            __('Dear %s,

Your reservation has been confirmed!

Reservation Details:
- Confirmation Code: %s
- Date: %s
- Time: %s
- Party Size: %d guests
- Special Requests: %s

Thank you for choosing us!

Best regards,
Restaurant Team', 'yrr'),
            $reservation->customer_name,
            $reservation->reservation_code,
            date('F j, Y', strtotime($reservation->reservation_date)),
            date('g:i A', strtotime($reservation->reservation_time)),
            $reservation->party_size,
            $reservation->special_requests ?: __('None', 'yrr')
        );
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: Restaurant <noreply@example.com>'
        );
        
        return wp_mail($reservation->customer_email, $subject, $message, $headers);
    }
}
