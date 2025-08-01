<?php
/**
 * Locations Model for Yenolx Restaurant Reservation System
 *
 * This class handles all data operations for locations.
 *
 * @package YRR/Models
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YRR_Locations_Model {

    /**
     * Get a single location by its ID.
     *
     * @param int $id The ID of the location.
     * @return object|null The location object, or null if not found.
     */
    public static function get_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . YRR_LOCATIONS_TABLE . " WHERE id = %d", $id));
    }

    /**
     * Get all locations.
     *
     * @param bool $active_only If true, returns only active locations.
     * @return array An array of location objects.
     */
    public static function get_all($active_only = false) {
        global $wpdb;
        $where_sql = $active_only ? "WHERE is_active = 1" : "";
        return $wpdb->get_results("SELECT * FROM " . YRR_LOCATIONS_TABLE . " $where_sql ORDER BY name ASC");
    }
    
    /**
     * Get the default location.
     *
     * @return object|null The default location object.
     */
    public static function get_default() {
        global $wpdb;
        $default = $wpdb->get_row("SELECT * FROM " . YRR_LOCATIONS_TABLE . " WHERE is_default = 1 AND is_active = 1 LIMIT 1");
        if (!$default) {
            $default = $wpdb->get_row("SELECT * FROM " . YRR_LOCATIONS_TABLE . " WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        }
        return $default;
    }

    /**
     * Create a new location.
     *
     * @param array $data The data for the new location.
     * @return int|WP_Error The new location ID on success, or a WP_Error object on failure.
     */
    public static function create($data) {
        global $wpdb;
        
        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('Location name is required.', 'yrr'));
        }
        
        $defaults = array(
            'name' => '',
            'address' => '',
            'phone' => '',
            'email' => '',
            'is_active' => 1,
            'is_default' => 0,
            'created_at' => current_time('mysql'),
        );
        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert(YRR_LOCATIONS_TABLE, $data);

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create location.', 'yrr'));
        }

        $location_id = $wpdb->insert_id;
        
        // If no other locations exist, make this the default
        $total_locations = $wpdb->get_var("SELECT COUNT(*) FROM " . YRR_LOCATIONS_TABLE);
        if ($total_locations == 1) {
            self::set_as_default($location_id);
        }
        
        do_action('yrr_location_created', $location_id, $data);
        return $location_id;
    }

    /**
     * Update an existing location.
     *
     * @param int   $id   The ID of the location to update.
     * @param array $data The new data for the location.
     * @return bool True on success, false on failure.
     */
    public static function update($id, $data) {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        $result = $wpdb->update(YRR_LOCATIONS_TABLE, $data, array('id' => $id));
        
        if ($result !== false) {
            do_action('yrr_location_updated', $id, $data);
        }
        
        return $result !== false;
    }

    /**
     * Delete a location.
     *
     * @param int $id The ID of the location to delete.
     * @return bool|WP_Error True on success, or a WP_Error object on failure.
     */
    public static function delete($id) {
        global $wpdb;

        // Prevent deletion of the default location if it's the only one left
        $location = self::get_by_id($id);
        if ($location && $location->is_default) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM " . YRR_LOCATIONS_TABLE . " WHERE is_active = 1");
            if ($count <= 1) {
                 return new WP_Error('cannot_delete_default', __('You cannot delete the only active default location.', 'yrr'));
            }
        }
        
        // Check for associated reservations
        $reservations_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . YRR_RESERVATIONS_TABLE . " WHERE location_id = %d", $id));
        if ($reservations_count > 0) {
            return new WP_Error('location_has_reservations', __('Cannot delete a location with existing reservations. Please reassign them first.', 'yrr'));
        }

        $result = $wpdb->delete(YRR_LOCATIONS_TABLE, array('id' => $id));

        if ($result !== false) {
            // Delete associated tables and hours
            $wpdb->delete(YRR_TABLES_TABLE, array('location_id' => $id));
            $wpdb->delete(YRR_HOURS_TABLE, array('location_id' => $id));
            do_action('yrr_location_deleted', $id);
        }
        
        return $result !== false;
    }
    
    /**
     * Set a location as the default.
     *
     * @param int $id The ID of the location to set as default.
     */
    public static function set_as_default($id) {
        global $wpdb;
        // First, unset all other defaults
        $wpdb->update(YRR_LOCATIONS_TABLE, array('is_default' => 0), array('is_default' => 1));
        // Then, set the new default
        $wpdb->update(YRR_LOCATIONS_TABLE, array('is_default' => 1), array('id' => $id));
        
        do_action('yrr_default_location_changed', $id);
    }
}
