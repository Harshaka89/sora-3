<?php
/**
 * Reservation Model - Handles all reservation data operations
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

class YRR_Reservation_Model {
    
    /**
     * Create a new reservation
     */
    public static function create($data) {
        global $wpdb;
        
        // Generate unique reservation code
        $data['reservation_code'] = self::generate_reservation_code();
        
        // Set defaults
        $defaults = array(
            'location_id' => 1,
            'status' => YRR_Settings_Model::get_setting('auto_confirm', 0) ? 'confirmed' : 'pending',
            'original_price' => 0.00,
            'discount_amount' => 0.00,
            'final_price' => 0.00,
            'source' => 'admin',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        $required = array('customer_name', 'customer_email', 'customer_phone', 'party_size', 'reservation_date', 'reservation_time');
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Field %s is required.', 'yrr'), $field));
            }
        }
        
        // Validate party size
        $max_party = YRR_Settings_Model::get_setting('max_party_size', 12);
        if ($data['party_size'] > $max_party) {
            return new WP_Error('party_too_large', sprintf(__('Party size cannot exceed %d guests.', 'yrr'), $max_party));
        }
        
        // Check availability
        if (!self::is_slot_available($data['reservation_date'], $data['reservation_time'], $data['party_size'])) {
            return new WP_Error('slot_unavailable', __('The selected time slot is not available.', 'yrr'));
        }
        
        // Insert reservation
        $result = $wpdb->insert(YRR_RESERVATIONS_TABLE, $data);
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create reservation.', 'yrr'));
        }
        
        $reservation_id = $wpdb->insert_id;
        
        // Fire action hook
        do_action('yrr_reservation_created', $reservation_id, $data);
        
        return $reservation_id;
    }
    
    /**
     * Get reservation by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, t.table_number, t.location as table_location, l.name as location_name 
             FROM " . YRR_RESERVATIONS_TABLE . " r 
             LEFT JOIN " . YRR_TABLES_TABLE . " t ON r.table_id = t.id 
             LEFT JOIN " . YRR_LOCATIONS_TABLE . " l ON r.location_id = l.id 
             WHERE r.id = %d",
            $id
        ));
    }
    
    /**
     * Get reservation by code
     */
    public static function get_by_code($code) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, t.table_number, t.location as table_location, l.name as location_name 
             FROM " . YRR_RESERVATIONS_TABLE . " r 
             LEFT JOIN " . YRR_TABLES_TABLE . " t ON r.table_id = t.id 
             LEFT JOIN " . YRR_LOCATIONS_TABLE . " l ON r.location_id = l.id 
             WHERE r.reservation_code = %s",
            $code
        ));
    }
    
    /**
     * Update reservation
     */
    public static function update($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            YRR_RESERVATIONS_TABLE,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            do_action('yrr_reservation_updated', $id, $data);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete reservation
     */
    public static function delete($id) {
        global $wpdb;
        
        $reservation = self::get_by_id($id);
        
        $result = $wpdb->delete(
            YRR_RESERVATIONS_TABLE,
            array('id' => $id),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('yrr_reservation_deleted', $id, $reservation);
        }
        
        return $result !== false;
    }
    
    /**
     * Get all reservations with pagination and filters
     */
    public static function get_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'location_id' => null,
            'status' => null,
            'date_from' => null,
            'date_to' => null,
            'table_id' => null,
            'search' => null,
            'orderby' => 'reservation_date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        // Apply filters
        if (!empty($args['location_id'])) {
            $where_clauses[] = "r.location_id = %d";
            $where_values[] = $args['location_id'];
        }
        
        if (!empty($args['status'])) {
            $where_clauses[] = "r.status = %s";
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['date_from'])) {
            $where_clauses[] = "r.reservation_date >= %s";
            $where_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_clauses[] = "r.reservation_date <= %s";
            $where_values[] = $args['date_to'];
        }
        
        if (!empty($args['table_id'])) {
            $where_clauses[] = "r.table_id = %d";
            $where_values[] = $args['table_id'];
        }
        
        if (!empty($args['search'])) {
            $where_clauses[] = "(r.customer_name LIKE %s OR r.customer_email LIKE %s OR r.reservation_code LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $order_sql = sprintf('ORDER BY r.%s %s', 
            sanitize_sql_orderby($args['orderby']), 
            strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC'
        );
        
        $sql = "SELECT r.*, t.table_number, t.location as table_location, l.name as location_name 
                FROM " . YRR_RESERVATIONS_TABLE . " r 
                LEFT JOIN " . YRR_TABLES_TABLE . " t ON r.table_id = t.id 
                LEFT JOIN " . YRR_LOCATIONS_TABLE . " l ON r.location_id = l.id 
                $where_sql 
                $order_sql 
                LIMIT %d OFFSET %d";
        
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        return $wpdb->get_results($wpdb->prepare($sql, $where_values));
    }
    
    /**
     * Get total count of reservations
     */
    public static function get_total_count($filters = array()) {
        global $wpdb;
        
        $where_clauses = array();
        $where_values = array();
        
        // Apply same filters as get_all
        if (!empty($filters['location_id'])) {
            $where_clauses[] = "location_id = %d";
            $where_values[] = $filters['location_id'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = "status = %s";
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = "reservation_date >= %s";
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = "reservation_date <= %s";
            $where_values[] = $filters['date_to'];
        }
        
        if (!empty($filters['table_id'])) {
            $where_clauses[] = "table_id = %d";
            $where_values[] = $filters['table_id'];
        }
        
        if (!empty($filters['search'])) {
            $where_clauses[] = "(customer_name LIKE %s OR customer_email LIKE %s OR reservation_code LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        $sql = "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " $where_sql";

        if (!empty($where_values)) {
            return intval($wpdb->get_var($wpdb->prepare($sql, $where_values)));
        } else {
            return intval($wpdb->get_var($sql));
        }
    }

    /**
     * Count total covers (guests) for reservations
     */
    public static function count_covers($filters = array()) {
        global $wpdb;

        $where_clauses = array();
        $where_values = array();

        if (!empty($filters['location_id'])) {
            $where_clauses[] = "location_id = %d";
            $where_values[] = $filters['location_id'];
        }

        if (!empty($filters['status'])) {
            $where_clauses[] = "status = %s";
            $where_values[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where_clauses[] = "reservation_date >= %s";
            $where_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_clauses[] = "reservation_date <= %s";
            $where_values[] = $filters['date_to'];
        }

        if (!empty($filters['table_id'])) {
            $where_clauses[] = "table_id = %d";
            $where_values[] = $filters['table_id'];
        }

        if (!empty($filters['search'])) {
            $where_clauses[] = "(customer_name LIKE %s OR customer_email LIKE %s OR reservation_code LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        $sql = "SELECT SUM(party_size) FROM " . YRR_RESERVATIONS_TABLE . " $where_sql";

        if (!empty($where_values)) {
            $result = $wpdb->get_var($wpdb->prepare($sql, $where_values));
        } else {
            $result = $wpdb->get_var($sql);
        }

        return intval($result);
    }

    /**
     * Calculate total revenue for the current month
     */
    public static function total_revenue_this_month() {
        global $wpdb;

        $start_of_month = date('Y-m-01', current_time('timestamp'));
        $end_of_month   = date('Y-m-t', current_time('timestamp'));

        $sql = $wpdb->prepare(
            "SELECT SUM(final_price) FROM " . YRR_RESERVATIONS_TABLE . " WHERE reservation_date BETWEEN %s AND %s AND status IN ('confirmed', 'completed')",
            $start_of_month,
            $end_of_month
        );

        $result = $wpdb->get_var($sql);

        return floatval($result);
    }

    /**
     * Get reservations by date range
     */
    public static function get_by_date_range($start_date, $end_date, $location_id = null) {
        global $wpdb;
        
        $where_clauses = array(
            "r.reservation_date BETWEEN %s AND %s"
        );
        $where_values = array($start_date, $end_date);
        
        if ($location_id) {
            $where_clauses[] = "r.location_id = %d";
            $where_values[] = $location_id;
        }
        
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, t.table_number, t.location as table_location, l.name as location_name 
             FROM " . YRR_RESERVATIONS_TABLE . " r 
             LEFT JOIN " . YRR_TABLES_TABLE . " t ON r.table_id = t.id 
             LEFT JOIN " . YRR_LOCATIONS_TABLE . " l ON r.location_id = l.id 
             $where_sql 
             ORDER BY r.reservation_date ASC, r.reservation_time ASC",
            $where_values
        ));
    }
    
    /**
     * Check if a time slot is available
     */
    public static function is_slot_available($date, $time, $party_size, $exclude_id = null) {
        global $wpdb;
        
        // Check if we have tables with sufficient capacity
        $available_tables = YRR_Tables_Model::get_available_by_capacity($party_size, $date, $time, $exclude_id);
        
        return !empty($available_tables);
    }
    
    /**
     * Get busy slots for a specific date
     */
    public static function get_busy_slots($date, $location_id = null) {
        global $wpdb;
        
        $where_clauses = array(
            "reservation_date = %s",
            "status IN ('confirmed', 'pending')"
        );
        $where_values = array($date);
        
        if ($location_id) {
            $where_clauses[] = "location_id = %d";
            $where_values[] = $location_id;
        }
        
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        
        $reservations = $wpdb->get_results($wpdb->prepare(
            "SELECT reservation_time, table_id, party_size FROM " . YRR_RESERVATIONS_TABLE . " $where_sql",
            $where_values
        ));
        
        $busy_slots = array();
        $slot_duration = YRR_Settings_Model::get_setting('slot_duration', 60);
        
        foreach ($reservations as $reservation) {
            $start_time = strtotime($reservation->reservation_time);
            $end_time = $start_time + ($slot_duration * 60);
            
            // Mark all slots within this reservation's duration as busy
            for ($time = $start_time; $time < $end_time; $time += (15 * 60)) {
                $slot_time = date('H:i', $time);
                if (!in_array($slot_time, $busy_slots)) {
                    $busy_slots[] = $slot_time;
                }
            }
        }
        
        return $busy_slots;
    }
    
    /**
     * Get dashboard statistics
     */
    public static function get_dashboard_stats($location_id = null) {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $week_end = date('Y-m-d', strtotime('sunday this week'));
        
        $stats = array();
        
        $location_where = '';
        if ($location_id) {
            $location_where = $wpdb->prepare(" AND location_id = %d", $location_id);
        }
        
        // Total reservations
        $stats['total'] = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE 1=1" . $location_where
        ));
        
        // Today's reservations
        $stats['today'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE reservation_date = %s" . $location_where,
            $today
        )));
        
        // This week's reservations
        $stats['this_week'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE reservation_date BETWEEN %s AND %s" . $location_where,
            $week_start,
            $week_end
        )));
        
        // Pending reservations
        $stats['pending'] = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE status = 'pending'" . $location_where
        ));
        
        // Confirmed reservations
        $stats['confirmed'] = intval($wpdb->get_var(
            "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE status = 'confirmed'" . $location_where
        ));
        
        // Total revenue
        $stats['revenue'] = floatval($wpdb->get_var(
            "SELECT SUM(final_price) FROM " . YRR_RESERVATIONS_TABLE . " WHERE status = 'confirmed'" . $location_where
        ));
        
        // Today's guests
        $stats['today_guests'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT SUM(party_size) FROM " . YRR_RESERVATIONS_TABLE . " WHERE reservation_date = %s AND status IN ('confirmed', 'pending')" . $location_where,
            $today
        )));
        
        return $stats;
    }
    
    /**
     * Generate unique reservation code
     */
    private static function generate_reservation_code() {
        global $wpdb;
        
        do {
            $code = 'YRR' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE reservation_code = %s",
                $code
            ));
        } while ($exists > 0);
        
        return $code;
    }
    
    /**
     * Update reservation status
     */
    public static function update_status($id, $status) {
        $valid_statuses = array('pending', 'confirmed', 'cancelled', 'completed', 'no_show');
        
        if (!in_array($status, $valid_statuses)) {
            return new WP_Error('invalid_status', __('Invalid reservation status.', 'yrr'));
        }
        
        $result = self::update($id, array('status' => $status));
        
        if ($result) {
            do_action('yrr_reservation_status_changed', $id, $status);
        }
        
        return $result;
    }
    
    /**
     * Get customer reservations by email
     */
    public static function get_by_customer_email($email, $limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, t.table_number, t.location as table_location, l.name as location_name 
             FROM " . YRR_RESERVATIONS_TABLE . " r 
             LEFT JOIN " . YRR_TABLES_TABLE . " t ON r.table_id = t.id 
             LEFT JOIN " . YRR_LOCATIONS_TABLE . " l ON r.location_id = l.id 
             WHERE r.customer_email = %s 
             ORDER BY r.reservation_date DESC, r.reservation_time DESC 
             LIMIT %d",
            $email,
            $limit
        ));
    }
}
