<?php
/**
 * AJAX Controller - Handles all AJAX requests for admin and public interfaces
 * Provides real-time functionality for availability checking, bookings, and data updates
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

class YRR_Ajax_Controller {
    
    /**
     * Initialize AJAX controller
     */
    public function __construct() {
        $this->register_ajax_hooks();
    }
    
    /**
     * Register all AJAX hooks
     */
    private function register_ajax_hooks() {
        // Admin AJAX hooks
        add_action('wp_ajax_yrr_get_available_slots', array($this, 'get_available_slots'));
        add_action('wp_ajax_yrr_get_available_tables', array($this, 'get_available_tables'));
        add_action('wp_ajax_yrr_get_dashboard_stats', array($this, 'get_dashboard_stats'));
        add_action('wp_ajax_yrr_validate_coupon', array($this, 'validate_coupon'));
        
        // Public AJAX hooks (both logged in and non-logged in users)
        add_action('wp_ajax_yrr_public_get_slots', array($this, 'public_get_available_slots'));
        add_action('wp_ajax_nopriv_yrr_public_get_slots', array($this, 'public_get_available_slots'));
        
        add_action('wp_ajax_yrr_public_validate_coupon', array($this, 'public_validate_coupon'));
        add_action('wp_ajax_nopriv_yrr_public_validate_coupon', array($this, 'public_validate_coupon'));
        
        add_action('wp_ajax_yrr_public_create_reservation', array($this, 'public_create_reservation'));
        add_action('wp_ajax_nopriv_yrr_public_create_reservation', array($this, 'public_create_reservation'));
        
        add_action('wp_ajax_yrr_public_get_my_reservations', array($this, 'public_get_my_reservations'));
        add_action('wp_ajax_nopriv_yrr_public_get_my_reservations', array($this, 'public_get_my_reservations'));
    }
    
    /**
     * ADMIN: Get available time slots
     */
    public function get_available_slots() {
        // Verify nonce for admin requests
        if (!wp_verify_nonce($_POST['nonce'], 'yrr_admin_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'yrr'));
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'yrr'));
            return;
        }
        
        $date = sanitize_text_field($_POST['date']);
        $party_size = intval($_POST['party_size']);
        $location_id = intval($_POST['location_id']) ?: 1;
        
        if (!$date || !$party_size) {
            wp_send_json_error(__('Date and party size are required.', 'yrr'));
            return;
        }
        
        $slots = $this->generate_time_slots($date, $party_size, $location_id);
        
        wp_send_json_success($slots);
    }
    
    /**
     * ADMIN: Get available tables for specific slot
     */
    public function get_available_tables() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'yrr_admin_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'yrr'));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'yrr'));
            return;
        }
        
        $date = sanitize_text_field($_POST['date']);
        $time = sanitize_text_field($_POST['time']);
        $party_size = intval($_POST['party_size']);
        $location_id = intval($_POST['location_id']) ?: 1;
        
        if (!$date || !$time || !$party_size) {
            wp_send_json_error(__('Date, time and party size are required.', 'yrr'));
            return;
        }
        
        $tables = YRR_Tables_Model::get_available_by_capacity($party_size, $date, $time);
        
        // Filter by location
        if ($location_id) {
            $tables = array_filter($tables, function($table) use ($location_id) {
                return $table->location_id == $location_id;
            });
        }
        
        wp_send_json_success(array_values($tables));
    }
    
    /**
     * ADMIN: Get dashboard statistics
     */
    public function get_dashboard_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'yrr_admin_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'yrr'));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'yrr'));
            return;
        }
        
        $location_id = !empty($_POST['location_id']) ? intval($_POST['location_id']) : null;
        $stats = YRR_Reservation_Model::get_dashboard_stats($location_id);
        
        wp_send_json_success($stats);
    }
    
    /**
     * ADMIN: Validate coupon code
     */
    public function validate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'yrr_admin_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'yrr'));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'yrr'));
            return;
        }
        
        $code = strtoupper(sanitize_text_field($_POST['code']));
        $total = floatval($_POST['total']) ?: 0;
        $customer_email = sanitize_email($_POST['customer_email']) ?: '';
        
        if (!$code) {
            wp_send_json_error(__('Coupon code is required.', 'yrr'));
            return;
        }
        
        $coupon = YRR_Coupons_Model::validate_coupon($code, $total, $customer_email);
        
        if (is_wp_error($coupon)) {
            wp_send_json_error($coupon->get_error_message());
            return;
        }
        
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
     * PUBLIC: Get available time slots
     */
    public function public_get_available_slots() {
        if (!wp_verify_nonce($_POST['nonce'], 'yrr_public_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'yrr'));
            return;
        }
        
        $date = sanitize_text_field($_POST['date']);
        $party_size = intval($_POST['party_size']);
        $location_id = intval($_POST['location_id']) ?: 1;
        
        if (!$date || !$party_size) {
            wp_send_json_error(__('Date and party size are required.', 'yrr'));
            return;
        }
        
        $slots = $this->generate_time_slots($date, $party_size, $location_id, true);
        
        wp_send_json_success($slots);
    }
    
    /**
     * PUBLIC: Validate coupon code
     */
    public function public_validate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'yrr_public_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'yrr'));
            return;
        }
        
        $code = strtoupper(sanitize_text_field($_POST['code']));
        $total = floatval($_POST['total']) ?: 0;
        $customer_email = sanitize_email($_POST['customer_email']) ?: '';
        
        if (!$code) {
            wp_send_json_error(__('Please enter a coupon code.', 'yrr'));
            return;
        }
        
        $coupon = YRR_Coupons_Model::validate_coupon($code, $total, $customer_email);
        
        if (is_wp_error($coupon)) {
            wp_send_json_error($coupon->get_error_message());
            return;
        }
        
        $discount = YRR_Coupons_Model::calculate_discount($coupon, $total);
        $final_total = max(0, $total - $discount);
        
        wp_send_json_success(array(
            'code' => $code,
            'discount_amount' => $discount,
            'final_total' => $final_total,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'message' => sprintf(__('Coupon applied! You saved %s%s', 'yrr'), YRR_Settings_Model::get_setting('currency_symbol', '$'), number_format($discount, 2))
        ));
    }
    
    /**
     * PUBLIC: Create reservation
     */
    public function public_create_reservation() {
        if (!wp_verify_nonce($_POST['nonce'], 'yrr_public_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'yrr'));
            return;
        }
        
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
        
        // Update coupon usage if used
        if ($data['coupon_code']) {
            $coupon = YRR_Coupons_Model::get_by_code($data['coupon_code']);
            if ($coupon) {
                YRR_Coupons_Model::increment_usage($coupon->id);
            }
        }
        
        wp_send_json_success(array(
            'reservation_code' => $reservation->reservation_code,
            'message' => __('Your reservation has been created successfully!', 'yrr'),
            'reservation_id' => $reservation_id
        ));
    }
    
    /**
     * PUBLIC: Get customer reservations
     */
    public function public_get_my_reservations() {
        if (!wp_verify_nonce($_POST['nonce'], 'yrr_public_nonce')) {
            wp_send_json_error(__('Security verification failed.', 'yrr'));
            return;
        }
        
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
     * Generate time slots for a given date
     */
    private function generate_time_slots($date, $party_size, $location_id = 1, $public_view = false) {
        // Get operating hours for this day
        $day_of_week = date('l', strtotime($date));
        $hours = YRR_Hours_Model::get_hours_for_day($day_of_week, $location_id);
        
        if (!$hours || $hours->is_closed) {
            return array();
        }
        
        // Get settings
        $slot_duration = YRR_Settings_Model::get_setting('slot_duration', 60);
        $booking_buffer = YRR_Settings_Model::get_setting('booking_buffer_hours', 2);
        
        // Generate time slots
        $slots = array();
        $current_time = strtotime($hours->open_time);
        $close_time = strtotime($hours->close_time);
        
        // Adjust for last seating time
        if ($hours->last_seating_time) {
            $close_time = strtotime($hours->last_seating_time);
        }
        
        // Handle overnight service
        if ($close_time < $current_time) {
            $close_time += 24 * 3600; // Add 24 hours
        }
        
        // If booking for today, start from current time + buffer
        if ($date === current_time('Y-m-d')) {
            $now_plus_buffer = strtotime(current_time('H:i:s')) + ($booking_buffer * 3600);
            if ($now_plus_buffer > $current_time) {
                $current_time = $now_plus_buffer;
                // Round up to next slot
                $minutes = date('i', $current_time);
                $round_minutes = ceil($minutes / $slot_duration) * $slot_duration;
                $current_time = mktime(date('H', $current_time), $round_minutes, 0, date('m', $current_time), date('d', $current_time), date('Y', $current_time));
            }
        }
        
        while ($current_time <= $close_time) {
            $slot_time = date('H:i:s', $current_time);
            
            // Skip break times
            if ($hours->break_start && $hours->break_end) {
                $break_start = strtotime($hours->break_start);
                $break_end = strtotime($hours->break_end);
                
                if ($current_time >= $break_start && $current_time < $break_end) {
                    $current_time += 15 * 60; // Skip in 15-minute increments during break
                    continue;
                }
            }
            
            // Check if slot is available
            $is_available = $this->is_slot_available($date, $slot_time, $party_size, $location_id);
            
            if ($is_available || !$public_view) {
                $slots[] = array(
                    'time' => $slot_time,
                    'display' => date('g:i A', $current_time),
                    'available' => $is_available,
                    'class' => $is_available ? 'available' : 'unavailable'
                );
            }
            
            $current_time += $slot_duration * 60;
        }
        
        return $slots;
    }
    
    /**
     * Check if a specific time slot is available
     */
    private function is_slot_available($date, $time, $party_size, $location_id) {
        // Get available tables that can accommodate the party
        $available_tables = YRR_Tables_Model::get_available_by_capacity($party_size, $date, $time);
        
        if (empty($available_tables)) {
            return false;
        }
        
        // Filter by location
        if ($location_id) {
            $available_tables = array_filter($available_tables, function($table) use ($location_id) {
                return $table->location_id == $location_id;
            });
        }
        
        return !empty($available_tables);
    }
    
    /**
     * Send confirmation email
     */
    private function send_confirmation_email($reservation) {
        $restaurant_info = YRR_Settings_Model::get_restaurant_info();
        $email_settings = YRR_Settings_Model::get_email_settings();
        
        $subject = str_replace('{reservation_code}', $reservation->reservation_code, $email_settings['confirmation_subject']);
        
        $message = $this->get_email_template($reservation, $restaurant_info);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $email_settings['from_name'] . ' <' . $email_settings['from_address'] . '>'
        );
        
        return wp_mail($reservation->customer_email, $subject, $message, $headers);
    }
    
    /**
     * Get HTML email template
     */
    private function get_email_template($reservation, $restaurant_info) {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reservation Confirmation</title></head>';
        $html .= '<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">';
        
        // Header
        $html .= '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">';
        $html .= '<h1 style="margin: 0; font-size: 24px;">Reservation Confirmation</h1>';
        $html .= '</div>';
        
        // Content
        $html .= '<div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px;">';
        $html .= '<p style="font-size: 18px; margin-bottom: 20px;">Dear <strong>' . esc_html($reservation->customer_name) . '</strong>,</p>';
        $html .= '<p style="font-size: 16px; margin-bottom: 25px;">Your reservation has been confirmed! Here are the details:</p>';
        
        // Reservation Details
        $html .= '<div style="background: white; padding: 20px; border-radius: 6px; border-left: 4px solid #667eea; margin: 20px 0;">';
        $html .= '<h3 style="margin-top: 0; color: #2c3e50;">Reservation Details</h3>';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<tr><td style="padding: 8px 0; font-weight: bold; width: 140px;">Confirmation Code:</td><td style="padding: 8px 0; color: #667eea; font-weight: bold;">' . esc_html($reservation->reservation_code) . '</td></tr>';
        $html .= '<tr><td style="padding: 8px 0; font-weight: bold;">Date:</td><td style="padding: 8px 0;">' . date('l, F j, Y', strtotime($reservation->reservation_date)) . '</td></tr>';
        $html .= '<tr><td style="padding: 8px 0; font-weight: bold;">Time:</td><td style="padding: 8px 0;">' . date('g:i A', strtotime($reservation->reservation_time)) . '</td></tr>';
        $html .= '<tr><td style="padding: 8px 0; font-weight: bold;">Party Size:</td><td style="padding: 8px 0;">' . $reservation->party_size . ' ' . ($reservation->party_size === 1 ? 'guest' : 'guests') . '</td></tr>';
        
        if ($reservation->table_number) {
            $html .= '<tr><td style="padding: 8px 0; font-weight: bold;">Table:</td><td style="padding: 8px 0;">' . esc_html($reservation->table_number) . '</td></tr>';
        }
        
        if ($reservation->special_requests) {
            $html .= '<tr><td style="padding: 8px 0; font-weight: bold; vertical-align: top;">Special Requests:</td><td style="padding: 8px 0;">' . esc_html($reservation->special_requests) . '</td></tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        
        // Footer
        $html .= '<p style="font-size: 16px; margin: 25px 0 10px;">Thank you for choosing <strong>' . esc_html($restaurant_info['name']) . '</strong>!</p>';
        $html .= '<p style="font-size: 14px; color: #666; margin-bottom: 0;">Best regards,<br><strong>' . esc_html($restaurant_info['name']) . ' Team</strong></p>';
        
        if ($restaurant_info['phone']) {
            $html .= '<p style="font-size: 14px; color: #666; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">Questions? Call us at <strong>' . esc_html($restaurant_info['phone']) . '</strong></p>';
        }
        
        $html .= '</div>';
        $html .= '</body></html>';
        
        return $html;
    }
}
