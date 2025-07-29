<?php
/**
 * Coupons Model - Handles discount codes and promotional offers
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

class YRR_Coupons_Model {
    
    /**
     * Create a new coupon
     */
    public static function create($data) {
        global $wpdb;
        
        // Set defaults
        $defaults = array(
            'discount_type' => 'percentage',
            'minimum_amount' => 0.00,
            'maximum_discount' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'usage_limit_per_customer' => 1,
            'applicable_days' => 'all',
            'applicable_times' => 'all',
            'location_ids' => null,
            'is_active' => 1,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['code']) || empty($data['discount_value'])) {
            return new WP_Error('missing_field', __('Coupon code and discount value are required.', 'yrr'));
        }
        
        // Sanitize code
        $data['code'] = strtoupper(sanitize_text_field($data['code']));
        
        // Check for duplicate code
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_COUPONS_TABLE . " WHERE code = %s",
            $data['code']
        ));
        
        if ($existing > 0) {
            return new WP_Error('duplicate_code', __('Coupon code already exists.', 'yrr'));
        }
        
        // Insert coupon
        $result = $wpdb->insert(YRR_COUPONS_TABLE, $data);
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create coupon.', 'yrr'));
        }
        
        $coupon_id = $wpdb->insert_id;
        
        // Fire action hook
        do_action('yrr_coupon_created', $coupon_id, $data);
        
        return $coupon_id;
    }
    
    /**
     * Get coupon by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . YRR_COUPONS_TABLE . " WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Get coupon by code
     */
    public static function get_by_code($code) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . YRR_COUPONS_TABLE . " WHERE code = %s",
            strtoupper($code)
        ));
    }
    
    /**
     * Update coupon
     */
    public static function update($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            YRR_COUPONS_TABLE,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            do_action('yrr_coupon_updated', $id, $data);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete coupon
     */
    public static function delete($id) {
        global $wpdb;
        
        $coupon = self::get_by_id($id);
        
        $result = $wpdb->delete(
            YRR_COUPONS_TABLE,
            array('id' => $id),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('yrr_coupon_deleted', $id, $coupon);
        }
        
        return $result !== false;
    }
    
    /**
     * Get all coupons with filters
     */
    public static function get_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'is_active' => null,
            'search' => null,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        // Apply filters
        if ($args['is_active'] !== null) {
            $where_clauses[] = "is_active = %d";
            $where_values[] = $args['is_active'];
        }
        
        if (!empty($args['search'])) {
            $where_clauses[] = "(code LIKE %s OR description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $order_sql = sprintf('ORDER BY %s %s', 
            sanitize_sql_orderby($args['orderby']), 
            strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'
        );
        
        $sql = "SELECT * FROM " . YRR_COUPONS_TABLE . " $where_sql $order_sql LIMIT %d OFFSET %d";
        
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($sql, $where_values));
    }
    
    /**
     * Validate coupon for use
     */
    public static function validate_coupon($code, $reservation_total = 0, $customer_email = null) {
        $coupon = self::get_by_code($code);
        
        if (!$coupon) {
            return new WP_Error('invalid_code', __('Invalid coupon code.', 'yrr'));
        }
        
        // Check if active
        if (!$coupon->is_active) {
            return new WP_Error('inactive_coupon', __('This coupon is no longer active.', 'yrr'));
        }
        
        // Check validity dates
        if ($coupon->valid_from && strtotime($coupon->valid_from) > current_time('timestamp')) {
            return new WP_Error('not_yet_valid', __('This coupon is not yet valid.', 'yrr'));
        }
        
        if ($coupon->valid_to && strtotime($coupon->valid_to) < current_time('timestamp')) {
            return new WP_Error('expired', __('This coupon has expired.', 'yrr'));
        }
        
        // Check minimum amount
        if ($reservation_total < $coupon->minimum_amount) {
            return new WP_Error('minimum_not_met', 
                sprintf(__('Minimum order amount of %s%s required.', 'yrr'), 
                    YRR_Settings_Model::get_setting('currency_symbol', '$'), 
                    number_format($coupon->minimum_amount, 2)
                )
            );
        }
        
        // Check usage limit
        if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
            return new WP_Error('usage_limit_reached', __('This coupon has reached its usage limit.', 'yrr'));
        }
        
        // Check per-customer usage limit
        if ($customer_email && $coupon->usage_limit_per_customer) {
            $customer_usage = self::get_customer_usage_count($coupon->code, $customer_email);
            if ($customer_usage >= $coupon->usage_limit_per_customer) {
                return new WP_Error('customer_limit_reached', __('You have already used this coupon the maximum number of times.', 'yrr'));
            }
        }
        
        // Check day restrictions
        if ($coupon->applicable_days !== 'all') {
            $current_day = date('l');
            $applicable_days = explode(',', $coupon->applicable_days);
            if (!in_array($current_day, $applicable_days)) {
                return new WP_Error('day_restriction', __('This coupon is not valid today.', 'yrr'));
            }
        }
        
        // Check time restrictions
        if ($coupon->applicable_times !== 'all') {
            $current_time = date('H:i');
            // Parse time range (e.g., "11:00-14:00")
            if (preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', $coupon->applicable_times, $matches)) {
                $start_time = $matches[1];
                $end_time = $matches[2];
                
                if ($current_time < $start_time || $current_time > $end_time) {
                    return new WP_Error('time_restriction', 
                        sprintf(__('This coupon is only valid between %s and %s.', 'yrr'), $start_time, $end_time)
                    );
                }
            }
        }
        
        return $coupon;
    }
    
    /**
     * Calculate discount amount
     */
    public static function calculate_discount($coupon, $total) {
        $discount = 0;
        
        if ($coupon->discount_type === 'percentage') {
            $discount = ($total * $coupon->discount_value) / 100;
        } else {
            $discount = $coupon->discount_value;
        }
        
        // Apply maximum discount limit
        if ($coupon->maximum_discount && $discount > $coupon->maximum_discount) {
            $discount = $coupon->maximum_discount;
        }
        
        // Don't allow discount to exceed total
        if ($discount > $total) {
            $discount = $total;
        }
        
        return round($discount, 2);
    }
    
    /**
     * Apply coupon to reservation
     */
    public static function apply_coupon($reservation_id, $coupon_code) {
        global $wpdb;
        
        $reservation = YRR_Reservation_Model::get_by_id($reservation_id);
        if (!$reservation) {
            return new WP_Error('invalid_reservation', __('Invalid reservation.', 'yrr'));
        }
        
        $coupon = self::validate_coupon($coupon_code, $reservation->original_price, $reservation->customer_email);
        if (is_wp_error($coupon)) {
            return $coupon;
        }
        
        $discount_amount = self::calculate_discount($coupon, $reservation->original_price);
        $final_price = $reservation->original_price - $discount_amount;
        
        // Update reservation
        $result = YRR_Reservation_Model::update($reservation_id, array(
            'coupon_code' => $coupon_code,
            'discount_amount' => $discount_amount,
            'final_price' => $final_price
        ));
        
        if ($result) {
            // Increment usage count
            self::increment_usage($coupon->id);
            
            do_action('yrr_coupon_applied', $reservation_id, $coupon_code, $discount_amount);
        }
        
        return $result;
    }
    
    /**
     * Increment coupon usage count
     */
    public static function increment_usage($coupon_id) {
        global $wpdb;
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE " . YRR_COUPONS_TABLE . " SET usage_count = usage_count + 1 WHERE id = %d",
            $coupon_id
        ));
    }
    
    /**
     * Get customer usage count for a coupon
     */
    public static function get_customer_usage_count($coupon_code, $customer_email) {
        global $wpdb;
        
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " 
             WHERE coupon_code = %s AND customer_email = %s AND status != 'cancelled'",
            $coupon_code,
            $customer_email
        )));
    }
    
    /**
     * Get coupon usage statistics
     */
    public static function get_usage_stats($coupon_id) {
        global $wpdb;
        
        $coupon = self::get_by_id($coupon_id);
        if (!$coupon) {
            return null;
        }
        
        $stats = array(
            'total_uses' => $coupon->usage_count,
            'remaining_uses' => $coupon->usage_limit ? max(0, $coupon->usage_limit - $coupon->usage_count) : 'unlimited',
            'total_discount_given' => 0,
            'recent_uses' => array()
        );
        
        // Calculate total discount given
        $total_discount = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(discount_amount) FROM " . YRR_RESERVATIONS_TABLE . " 
             WHERE coupon_code = %s AND status != 'cancelled'",
            $coupon->code
        ));
        
        $stats['total_discount_given'] = floatval($total_discount);
        
        // Get recent uses
        $recent_uses = $wpdb->get_results($wpdb->prepare(
            "SELECT customer_name, customer_email, discount_amount, reservation_date, created_at 
             FROM " . YRR_RESERVATIONS_TABLE . " 
             WHERE coupon_code = %s AND status != 'cancelled' 
             ORDER BY created_at DESC 
             LIMIT 10",
            $coupon->code
        ));
        
        $stats['recent_uses'] = $recent_uses;
        
        return $stats;
    }
}
