<?php
/**
 * Points Model - Handles loyalty points and rewards system
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

class YRR_Points_Model {
    
    /**
     * Award points for a reservation
     */
    public static function award_points($reservation_id) {
        if (!YRR_Settings_Model::get_setting('enable_loyalty', 0)) {
            return false;
        }
        
        $reservation = YRR_Reservation_Model::get_by_id($reservation_id);
        if (!$reservation) {
            return false;
        }
        
        // Check if points already awarded
        if (self::get_by_reservation($reservation_id)) {
            return false;
        }
        
        $points_per_dollar = YRR_Settings_Model::get_setting('points_per_dollar', 1);
        $points_earned = floor($reservation->final_price * $points_per_dollar);
        
        if ($points_earned <= 0) {
            return false;
        }
        
        return self::create_transaction(array(
            'customer_email' => $reservation->customer_email,
            'reservation_id' => $reservation_id,
            'points_earned' => $points_earned,
            'transaction_type' => 'earned',
            'description' => sprintf(__('Points earned for reservation #%s', 'yrr'), $reservation->reservation_code),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
        ));
    }
    
    /**
     * Create a points transaction
     */
    public static function create_transaction($data) {
        global $wpdb;
        
        $defaults = array(
            'points_earned' => 0,
            'points_redeemed' => 0,
            'transaction_type' => 'earned',
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['customer_email']) || empty($data['reservation_id'])) {
            return new WP_Error('missing_field', __('Customer email and reservation ID are required.', 'yrr'));
        }
        
        $result = $wpdb->insert(YRR_POINTS_TABLE, $data);
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create points transaction.', 'yrr'));
        }
        
        $transaction_id = $wpdb->insert_id;
        
        // Fire action hook
        do_action('yrr_points_transaction_created', $transaction_id, $data);
        
        return $transaction_id;
    }
    
    /**
     * Get customer points balance
     */
    public static function get_customer_balance($customer_email) {
        global $wpdb;
        
        $earned = intval($wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points_earned) FROM " . YRR_POINTS_TABLE . " 
             WHERE customer_email = %s AND (expires_at IS NULL OR expires_at > NOW())",
            $customer_email
        )));
        
        $redeemed = intval($wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points_redeemed) FROM " . YRR_POINTS_TABLE . " 
             WHERE customer_email = %s",
            $customer_email
        )));
        
        return max(0, $earned - $redeemed);
    }
    
    /**
     * Get customer points history
     */
    public static function get_customer_history($customer_email, $limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . YRR_POINTS_TABLE . " 
             WHERE customer_email = %s 
             ORDER BY created_at DESC 
             LIMIT %d",
            $customer_email,
            $limit
        ));
    }
    
    /**
     * Redeem points
     */
    public static function redeem_points($customer_email, $points_to_redeem, $reservation_id, $description = '') {
        $current_balance = self::get_customer_balance($customer_email);
        
        if ($points_to_redeem > $current_balance) {
            return new WP_Error('insufficient_points', __('Insufficient points balance.', 'yrr'));
        }
        
        return self::create_transaction(array(
            'customer_email' => $customer_email,
            'reservation_id' => $reservation_id,
            'points_redeemed' => $points_to_redeem,
            'transaction_type' => 'redeemed',
            'description' => $description ?: sprintf(__('Points redeemed for reservation #%s', 'yrr'), $reservation_id)
        ));
    }
    
    /**
     * Get points transaction by reservation
     */
    public static function get_by_reservation($reservation_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . YRR_POINTS_TABLE . " WHERE reservation_id = %d",
            $reservation_id
        ));
    }
    
    /**
     * Expire points
     */
    public static function expire_points() {
        global $wpdb;
        
        $expired = $wpdb->get_results(
            "SELECT * FROM " . YRR_POINTS_TABLE . " 
             WHERE expires_at IS NOT NULL AND expires_at <= NOW() AND transaction_type = 'earned'"
        );
        
        foreach ($expired as $transaction) {
            self::create_transaction(array(
                'customer_email' => $transaction->customer_email,
                'reservation_id' => $transaction->reservation_id,
                'points_redeemed' => $transaction->points_earned,
                'transaction_type' => 'expired',
                'description' => __('Points expired', 'yrr')
            ));
        }
        
        return count($expired);
    }
    
    /**
     * Get loyalty program statistics
     */
    public static function get_program_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total points awarded
        $stats['total_awarded'] = intval($wpdb->get_var(
            "SELECT SUM(points_earned) FROM " . YRR_POINTS_TABLE
        ));
        
        // Total points redeemed
        $stats['total_redeemed'] = intval($wpdb->get_var(
            "SELECT SUM(points_redeemed) FROM " . YRR_POINTS_TABLE
        ));
        
        // Active customers (with points balance > 0)
        $stats['active_customers'] = intval($wpdb->get_var(
            "SELECT COUNT(DISTINCT customer_email) FROM " . YRR_POINTS_TABLE . " 
             WHERE points_earned > 0"
        ));
        
        // Average points per customer
        if ($stats['active_customers'] > 0) {
            $stats['avg_points_per_customer'] = round($stats['total_awarded'] / $stats['active_customers'], 2);
        } else {
            $stats['avg_points_per_customer'] = 0;
        }
        
        return $stats;
    }
}
