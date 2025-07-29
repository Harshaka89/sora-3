<?php
/**
 * Tables Model - Handles all table data operations and availability logic
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

class YRR_Tables_Model {
    
    /**
     * Create a new table
     */
    public static function create($data) {
        global $wpdb;
        
        // Set defaults
        $defaults = array(
            'location_id' => 1,
            'table_type' => 'standard',
            'status' => 'available',
            'shape' => 'square',
            'color' => '#2196F3',
            'position_x' => 0,
            'position_y' => 0,
            'is_active' => 1,
            'sort_order' => 0,
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['table_number']) || empty($data['capacity'])) {
            return new WP_Error('missing_field', __('Table number and capacity are required.', 'yrr'));
        }
        
        // Check for duplicate table number in same location
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_TABLES_TABLE . " WHERE table_number = %s AND location_id = %d",
            $data['table_number'],
            $data['location_id']
        ));
        
        if ($existing > 0) {
            return new WP_Error('duplicate_table', __('Table number already exists in this location.', 'yrr'));
        }
        
        // Insert table
        $result = $wpdb->insert(YRR_TABLES_TABLE, $data);
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create table.', 'yrr'));
        }
        
        $table_id = $wpdb->insert_id;
        
        // Fire action hook
        do_action('yrr_table_created', $table_id, $data);
        
        return $table_id;
    }
    
    /**
     * Get table by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, l.name as location_name 
             FROM " . YRR_TABLES_TABLE . " t 
             LEFT JOIN " . YRR_LOCATIONS_TABLE . " l ON t.location_id = l.id 
             WHERE t.id = %d",
            $id
        ));
    }
    
    /**
     * Update table
     */
    public static function update($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            YRR_TABLES_TABLE,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            do_action('yrr_table_updated', $id, $data);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete table
     */
    public static function delete($id) {
        global $wpdb;
        
        // Check if table has any reservations
        $reservations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE table_id = %d AND status IN ('confirmed', 'pending')",
            $id
        ));
        
        if ($reservations > 0) {
            return new WP_Error('table_has_reservations', __('Cannot delete table with existing reservations.', 'yrr'));
        }
        
        $table = self::get_by_id($id);
        
        $result = $wpdb->delete(
            YRR_TABLES_TABLE,
            array('id' => $id),
            array('%d')
        );
        
        if ($result !== false) {
            do_action('yrr_table_deleted', $id, $table);
        }
        
        return $result !== false;
    }
    
    /**
     * Get all tables with filters
     */
    public static function get_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'location_id' => null,
            'status' => null,
            'table_type' => null,
            'is_active' => 1,
            'orderby' => 'sort_order',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        // Apply filters
        if (!empty($args['location_id'])) {
            $where_clauses[] = "t.location_id = %d";
            $where_values[] = $args['location_id'];
        }
        
        if (!empty($args['status'])) {
            $where_clauses[] = "t.status = %s";
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['table_type'])) {
            $where_clauses[] = "t.table_type = %s";
            $where_values[] = $args['table_type'];
        }
        
        if ($args['is_active'] !== null) {
            $where_clauses[] = "t.is_active = %d";
            $where_values[] = $args['is_active'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $order_sql = sprintf('ORDER BY t.%s %s', 
            sanitize_sql_orderby($args['orderby']), 
            strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC'
        );
        
        $sql = "SELECT t.*, l.name as location_name 
                FROM " . YRR_TABLES_TABLE . " t 
                LEFT JOIN " . YRR_LOCATIONS_TABLE . " l ON t.location_id = l.id 
                $where_sql 
                $order_sql";
        
        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $where_values));
        } else {
            return $wpdb->get_results($sql);
        }
    }
    
    /**
     * Get available tables by capacity
     */
    public static function get_available_by_capacity($party_size, $date = null, $time = null, $exclude_reservation_id = null) {
        global $wpdb;
        
        $where_clauses = array(
            "t.capacity >= %d",
            "t.is_active = 1",
            "t.status = 'available'"
        );
        $where_values = array($party_size);
        
        // If date and time provided, check for conflicts
        if ($date && $time) {
            $slot_duration = YRR_Settings_Model::get_setting('slot_duration', 60);
            $start_time = $time;
            $end_time = date('H:i:s', strtotime($time) + ($slot_duration * 60));
            
            $conflict_sql = "
                AND t.id NOT IN (
                    SELECT DISTINCT r.table_id 
                    FROM " . YRR_RESERVATIONS_TABLE . " r 
                    WHERE r.reservation_date = %s 
                    AND r.status IN ('confirmed', 'pending')
                    AND r.table_id IS NOT NULL
                    AND (
                        (r.reservation_time BETWEEN %s AND %s)
                        OR (ADDTIME(r.reservation_time, SEC_TO_TIME(%d * 60)) BETWEEN %s AND %s)
                        OR (r.reservation_time <= %s AND ADDTIME(r.reservation_time, SEC_TO_TIME(%d * 60)) >= %s)
                    )";
            
            if ($exclude_reservation_id) {
                $conflict_sql .= " AND r.id != %d";
            }
            
            $conflict_sql .= ")";
            
            $where_clauses[] = $conflict_sql;
            $where_values[] = $date;
            $where_values[] = $start_time;
            $where_values[] = $end_time;
            $where_values[] = $slot_duration;
            $where_values[] = $start_time;
            $where_values[] = $end_time;
            $where_values[] = $start_time;
            $where_values[] = $slot_duration;
            $where_values[] = $end_time;
            
            if ($exclude_reservation_id) {
                $where_values[] = $exclude_reservation_id;
            }
        }
        
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        
        $sql = "SELECT t.*, l.name as location_name 
                FROM " . YRR_TABLES_TABLE . " t 
                LEFT JOIN " . YRR_LOCATIONS_TABLE . " l ON t.location_id = l.id 
                $where_sql 
                ORDER BY t.capacity ASC, t.sort_order ASC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $where_values));
    }
    
    /**
     * Get table availability for a specific date
     */
    public static function get_table_schedule($date, $location_id = null) {
        global $wpdb;
        
        // Get all active tables
        $tables_args = array('is_active' => 1);
        if ($location_id) {
            $tables_args['location_id'] = $location_id;
        }
        
        $tables = self::get_all($tables_args);
        
        // Get reservations for the date
        $reservations_args = array(
            'date_from' => $date,
            'date_to' => $date
        );
        if ($location_id) {
            $reservations_args['location_id'] = $location_id;
        }
        
        $reservations = YRR_Reservation_Model::get_all($reservations_args);
        
        // Build schedule array
        $schedule = array();
        
        foreach ($tables as $table) {
            $table_reservations = array_filter($reservations, function($reservation) use ($table) {
                return $reservation->table_id == $table->id;
            });
            
            $schedule[$table->id] = array(
                'table' => $table,
                'reservations' => array_values($table_reservations)
            );
        }
        
        return $schedule;
    }
    
    /**
     * Get table types
     */
    public static function get_table_types() {
        return apply_filters('yrr_table_types', array(
            'standard' => __('Standard', 'yrr'),
            'vip' => __('VIP', 'yrr'),
            'bar' => __('Bar Seating', 'yrr'),
            'outdoor' => __('Outdoor', 'yrr'),
            'private' => __('Private Dining', 'yrr'),
            'banquet' => __('Banquet', 'yrr'),
            'booth' => __('Booth', 'yrr'),
            'counter' => __('Counter', 'yrr')
        ));
    }
    
    /**
     * Get table shapes
     */
    public static function get_table_shapes() {
        return apply_filters('yrr_table_shapes', array(
            'square' => __('Square', 'yrr'),
            'rectangle' => __('Rectangle', 'yrr'),
            'circle' => __('Circle', 'yrr'),
            'oval' => __('Oval', 'yrr')
        ));
    }
    
    /**
     * Get table statuses
     */
    public static function get_table_statuses() {
        return apply_filters('yrr_table_statuses', array(
            'available' => __('Available', 'yrr'),
            'occupied' => __('Occupied', 'yrr'),
            'maintenance' => __('Maintenance', 'yrr'),
            'reserved' => __('Reserved', 'yrr')
        ));
    }
    
    /**
     * Update table status
     */
    public static function update_status($id, $status) {
        $valid_statuses = array_keys(self::get_table_statuses());
        
        if (!in_array($status, $valid_statuses)) {
            return new WP_Error('invalid_status', __('Invalid table status.', 'yrr'));
        }
        
        $result = self::update($id, array('status' => $status));
        
        if ($result) {
            do_action('yrr_table_status_changed', $id, $status);
        }
        
        return $result;
    }
    
    /**
     * Update table positions (for drag-and-drop)
     */
    public static function update_positions($positions) {
        global $wpdb;
        
        $updated = 0;
        
        foreach ($positions as $position) {
            if (isset($position['id'], $position['x'], $position['y'])) {
                $result = $wpdb->update(
                    YRR_TABLES_TABLE,
                    array(
                        'position_x' => intval($position['x']),
                        'position_y' => intval($position['y']),
                        'updated_at' => current_time('mysql')
                    ),
                    array('id' => intval($position['id'])),
                    array('%d', '%d', '%s'),
                    array('%d')
                );
                
                if ($result !== false) {
                    $updated++;
                }
            }
        }
        
        if ($updated > 0) {
            do_action('yrr_table_positions_updated', $positions);
        }
        
        return $updated;
    }
    
    /**
     * Get table utilization statistics
     */
    public static function get_utilization_stats($date_from, $date_to, $location_id = null) {
        global $wpdb;
        
        $where_clauses = array(
            "r.reservation_date BETWEEN %s AND %s",
            "r.status IN ('confirmed', 'completed')",
            "r.table_id IS NOT NULL"
        );
        $where_values = array($date_from, $date_to);
        
        if ($location_id) {
            $where_clauses[] = "r.location_id = %d";
            $where_values[] = $location_id;
        }
        
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                t.table_number,
                t.capacity,
                COUNT(r.id) as total_reservations,
                SUM(r.party_size) as total_guests,
                AVG(r.party_size) as avg_party_size,
                ROUND(AVG(r.party_size / t.capacity * 100), 2) as utilization_percent
             FROM " . YRR_TABLES_TABLE . " t
             LEFT JOIN " . YRR_RESERVATIONS_TABLE . " r ON t.id = r.table_id $where_sql
             GROUP BY t.id, t.table_number, t.capacity
             ORDER BY utilization_percent DESC",
            $where_values
        ));
        
        return $stats;
    }
    
    /**
     * Check if table can accommodate party size
     */
    public static function can_accommodate($table_id, $party_size) {
        $table = self::get_by_id($table_id);
        
        if (!$table) {
            return false;
        }
        
        return $table->capacity >= $party_size && 
               $table->min_capacity <= $party_size && 
               $table->is_active == 1;
    }
    
    /**
     * Get optimal table for party size
     */
    public static function get_optimal_table($party_size, $date = null, $time = null, $location_id = null) {
        // Get available tables that can accommodate the party
        $available_tables = self::get_available_by_capacity($party_size, $date, $time);
        
        if (empty($available_tables)) {
            return null;
        }
        
        // Filter by location if specified
        if ($location_id) {
            $available_tables = array_filter($available_tables, function($table) use ($location_id) {
                return $table->location_id == $location_id;
            });
        }
        
        if (empty($available_tables)) {
            return null;
        }
        
        // Sort by capacity (smallest first) and then by sort order
        usort($available_tables, function($a, $b) use ($party_size) {
            // Prefer tables closer to party size
            $a_efficiency = $a->capacity - $party_size;
            $b_efficiency = $b->capacity - $party_size;
            
            if ($a_efficiency === $b_efficiency) {
                return $a->sort_order - $b->sort_order;
            }
            
            return $a_efficiency - $b_efficiency;
        });
        
        return $available_tables[0];
    }
    
    /**
     * Get table colors palette
     */
    public static function get_color_palette() {
        return apply_filters('yrr_table_colors', array(
            '#2196F3' => __('Blue', 'yrr'),
            '#4CAF50' => __('Green', 'yrr'),
            '#FF9800' => __('Orange', 'yrr'),
            '#F44336' => __('Red', 'yrr'),
            '#9C27B0' => __('Purple', 'yrr'),
            '#00BCD4' => __('Cyan', 'yrr'),
            '#795548' => __('Brown', 'yrr'),
            '#607D8B' => __('Blue Grey', 'yrr'),
            '#E91E63' => __('Pink', 'yrr'),
            '#3F51B5' => __('Indigo', 'yrr')
        ));
    }
}
