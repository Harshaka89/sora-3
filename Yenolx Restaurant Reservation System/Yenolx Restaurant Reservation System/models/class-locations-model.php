<?php
/**
 * Locations Model - Handles multi-location restaurant management
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

class YRR_Locations_Model {
    
    /**
     * Create a new location
     */
    public static function create($data) {
        global $wpdb;
        
        // Set defaults
        $defaults = array(
            'timezone' => get_option('timezone_string') ?: 'UTC',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'is_active' => 1,
            'is_default' => 0,
            'sort_order' => 0,
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('Location name is required.', 'yrr'));
        }
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        }
        
        // Check for duplicate slug
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_LOCATIONS_TABLE . " WHERE slug = %s",
            $data['slug']
        ));
        
        if ($existing > 0) {
            $data['slug'] = $data['slug'] . '-' . time();
        }
        
        // Insert location
        $result = $wpdb->insert(YRR_LOCATIONS_TABLE, $data);
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create location.', 'yrr'));
        }
        
        $location_id = $wpdb->insert_id;
        
        // If this is the first location, make it default
        $total_locations = $wpdb->get_var("SELECT COUNT(*) FROM " . YRR_LOCATIONS_TABLE);
        if ($total_locations == 1) {
            self::set_as_default($location_id);
        }
        
        // Fire action hook
        do_action('yrr_location_created', $location_id, $data);
        
        return $location_id;
    }
    
    /**
     * Get location by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . YRR_LOCATIONS_TABLE . " WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Get location by slug
     */
    public static function get_by_slug($slug) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . YRR_LOCATIONS_TABLE . " WHERE slug = %s",
            $slug
        ));
    }
    
    /**
     * Update location
     */
    public static function update($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            YRR_LOCATIONS_TABLE,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            do_action('yrr_location_updated', $id, $data);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete location
     */
    public static function delete($id) {
        global $wpdb;
        
        // Check if location has reservations
        $reservations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE location_id = %d",
            $id
        ));
        
        if ($reservations > 0) {
            return new WP_Error('location_has_reservations', __('Cannot delete location with existing reservations.', 'yrr'));
        }
        
        // Check if this is the default location
        $location = self::get_by_id($id);
        if ($location && $location->is_default) {
            return new WP_Error('cannot_delete_default', __('Cannot delete the default location.', 'yrr'));
        }
        
        $result = $wpdb->delete(
            YRR_LOCATIONS_TABLE,
            array('id' => $id),
            array('%d')
        );
        
        if ($result !== false) {
            // Delete related data
            $wpdb->delete(YRR_TABLES_TABLE, array('location_id' => $id), array('%d'));
            $wpdb->delete(YRR_HOURS_TABLE, array('location_id' => $id), array('%d'));
            
            do_action('yrr_location_deleted', $id, $location);
        }
        
        return $result !== false;
    }
    
    /**
     * Get all locations
     */
    public static function get_all($active_only = false) {
        global $wpdb;
        
        $where_sql = '';
        if ($active_only) {
            $where_sql = "WHERE is_active = 1";
        }
        
        $sql = "SELECT * FROM " . YRR_LOCATIONS_TABLE . " $where_sql ORDER BY sort_order ASC, name ASC";
        // Prepare is used even without dynamic parameters to enforce safe query construction
        // and maintain consistency with WordPress database practices.
        return $wpdb->get_results($wpdb->prepare($sql, []));
    }
    
    /**
     * Get default location
     */
    public static function get_default() {
        global $wpdb;
        
        $default = $wpdb->get_row(
            "SELECT * FROM " . YRR_LOCATIONS_TABLE . " WHERE is_default = 1 LIMIT 1"
        );
        
        if (!$default) {
            // If no default set, get the first active location
            $default = $wpdb->get_row(
                "SELECT * FROM " . YRR_LOCATIONS_TABLE . " WHERE is_active = 1 ORDER BY id ASC LIMIT 1"
            );
        }
        
        return $default;
    }
    
    /**
     * Set location as default
     */
    public static function set_as_default($id) {
        global $wpdb;
        
        // Remove default from all locations
        $wpdb->update(
            YRR_LOCATIONS_TABLE,
            array('is_default' => 0),
            array(),
            array('%d'),
            array()
        );
        
        // Set new default
        $result = $wpdb->update(
            YRR_LOCATIONS_TABLE,
            array('is_default' => 1),
            array('id' => $id),
            array('%d'),
            array('%d')
        );
        
        if ($result !== false) {
            YRR_Settings_Model::set_setting('default_location', $id);
            do_action('yrr_default_location_changed', $id);
        }
        
        return $result !== false;
    }
    
    /**
     * Get location statistics
     */
    public static function get_location_stats($location_id) {
        global $wpdb;
        
        $stats = array();
        
        // Total reservations
        $stats['total_reservations'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE location_id = %d",
            $location_id
        )));
        
        // Total tables
        $stats['total_tables'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_TABLES_TABLE . " WHERE location_id = %d AND is_active = 1",
            $location_id
        )));
        
        // This month's reservations
        $this_month = date('Y-m-01');
        $stats['this_month'] = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE location_id = %d AND reservation_date >= %s",
            $location_id,
            $this_month
        )));
        
        // Revenue
        $stats['revenue'] = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT SUM(final_price) FROM " . YRR_RESERVATIONS_TABLE . " WHERE location_id = %d AND status = 'confirmed'",
            $location_id
        )));
        
        return $stats;
    }
    
    /**
     * Check if multi-location is enabled
     */
    public static function is_multi_location_enabled() {
        return YRR_Settings_Model::get_setting('multi_location_enabled', 0);
    }
    
    /**
     * Get locations for dropdown
     */
    public static function get_dropdown_options() {
        $locations = self::get_all(true);
        $options = array();
        
        foreach ($locations as $location) {
            $options[$location->id] = $location->name;
        }
        
        return $options;
    }
}
