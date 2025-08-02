<?php
/**
 * Hours Model - Manages restaurant operating hours and schedule
 * Handles daily hours, breaks, and location-specific schedules
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Prevent duplicate class declaration
if (!class_exists('YRR_Hours_Model')) {

class YRR_Hours_Model {
    
    /**
     * Database table name
     */
    private static $table_name = 'yrr_hours';
    
    /**
     * Get full table name with WordPress prefix
     */
    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::$table_name;
    }
    
    /**
     * Create hours table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            location_id int(11) NOT NULL DEFAULT 1,
            day_of_week varchar(10) NOT NULL,
            is_closed tinyint(1) NOT NULL DEFAULT 0,
            open_time time NULL,
            close_time time NULL,
            break_start time NULL,
            break_end time NULL,
            last_seating_time time NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY location_id (location_id),
            KEY day_of_week (day_of_week)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Insert default hours for all days
        self::insert_default_hours();
    }
    
    /**
     * Insert default operating hours
     */
    private static function insert_default_hours() {
        global $wpdb;
        $table_name = self::get_table_name();
        
        // Check if hours already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($existing > 0) {
            return; // Already have hours set up
        }
        
        // Default hours: 9 AM to 11 PM, Monday through Sunday
        $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        
        foreach ($days as $day) {
            $wpdb->insert(
                $table_name,
                array(
                    'location_id' => 1,
                    'day_of_week' => $day,
                    'is_closed' => 0,
                    'open_time' => '09:00:00',
                    'close_time' => '23:00:00',
                    'last_seating_time' => '22:30:00'
                ),
                array('%d', '%s', '%d', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Get all hours
     */
    public static function get_all($location_id = null) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $sql = "SELECT * FROM $table_name";
        $params = array();
        
        if ($location_id) {
            $sql .= " WHERE location_id = %d";
            $params[] = $location_id;
        }
        
        $sql .= " ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get hours for specific day
     */
    public static function get_hours_for_day($day_of_week, $location_id = 1) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE day_of_week = %s AND location_id = %d",
            $day_of_week,
            $location_id
        );
        
        $result = $wpdb->get_row($sql);
        
        // If no specific hours found, return default hours
        if (!$result) {
            return (object) array(
                'id' => 0,
                'location_id' => $location_id,
                'day_of_week' => $day_of_week,
                'is_closed' => 0,
                'open_time' => '09:00:00',
                'close_time' => '23:00:00',
                'break_start' => null,
                'break_end' => null,
                'last_seating_time' => '22:30:00'
            );
        }
        
        return $result;
    }
    
    /**
     * Update hours for specific day
     */
    public static function update_hours($id, $data) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        $allowed_fields = array(
            'is_closed',
            'open_time',
            'close_time',
            'break_start',
            'break_end',
            'last_seating_time'
        );
        
        $update_data = array();
        $format = array();
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
                $format[] = in_array($field, array('is_closed')) ? '%d' : '%s';
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        return $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
    }
    
    /**
     * Check if restaurant is open at specific day and time
     */
    public static function is_open($day_of_week, $time, $location_id = 1) {
        $hours = self::get_hours_for_day($day_of_week, $location_id);
        
        if (!$hours || $hours->is_closed) {
            return false;
        }
        
        $current_time = strtotime($time);
        $open_time = strtotime($hours->open_time);
        $close_time = strtotime($hours->close_time);
        
        // Handle overnight hours (close time next day)
        if ($close_time < $open_time) {
            $close_time += 24 * 3600; // Add 24 hours
        }
        
        // Check if within operating hours
        if ($current_time < $open_time || $current_time > $close_time) {
            return false;
        }
        
        // Check if within break time
        if ($hours->break_start && $hours->break_end) {
            $break_start = strtotime($hours->break_start);
            $break_end = strtotime($hours->break_end);
            
            if ($current_time >= $break_start && $current_time < $break_end) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get today's hours
     */
    public static function get_today_hours($location_id = 1) {
        $today = date('l'); // Full day name (Monday, Tuesday, etc.)
        return self::get_hours_for_day($today, $location_id);
    }
    
    /**
     * Check if restaurant is currently open
     */
    public static function is_currently_open($location_id = 1) {
        $today = date('l');
        $current_time = date('H:i:s');
        
        return self::is_open($today, $current_time, $location_id);
    }
    
    /**
     * Get formatted hours display
     */
    public static function get_formatted_hours($day_of_week, $location_id = 1) {
        $hours = self::get_hours_for_day($day_of_week, $location_id);
        
        if (!$hours || $hours->is_closed) {
            return __('Closed', 'yrr');
        }
        
        $open = date('g:i A', strtotime($hours->open_time));
        $close = date('g:i A', strtotime($hours->close_time));
        
        $formatted = $open . ' - ' . $close;
        
        if ($hours->break_start && $hours->break_end) {
            $break_start = date('g:i A', strtotime($hours->break_start));
            $break_end = date('g:i A', strtotime($hours->break_end));
            $formatted .= ' (' . __('Break:', 'yrr') . ' ' . $break_start . '-' . $break_end . ')';
        }
        
        return $formatted;
    }
    
    /**
     * Drop hours table
     */
    public static function drop_table() {
        global $wpdb;
        $table_name = self::get_table_name();
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}

} // End class_exists check
