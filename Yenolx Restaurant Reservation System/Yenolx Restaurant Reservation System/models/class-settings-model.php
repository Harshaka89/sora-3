<?php
/**
 * Settings Model - Handles all plugin settings and configuration
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

class YRR_Settings_Model {
    
    /**
     * Cache for settings
     */
    private static $cache = array();
    
    /**
     * Get a setting value
     */
    public static function get_setting($key, $default = null) {
        // Check cache first
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        global $wpdb;
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM " . YRR_SETTINGS_TABLE . " WHERE setting_key = %s",
            $key
        ));
        
        if ($value !== null) {
            $value = maybe_unserialize($value);
            self::$cache[$key] = $value;
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Set a setting value
     */
    public static function set_setting($key, $value) {
        global $wpdb;
        
        $result = $wpdb->replace(
            YRR_SETTINGS_TABLE,
            array(
                'setting_key' => $key,
                'setting_value' => maybe_serialize($value),
                'autoload' => 1
            ),
            array('%s', '%s', '%d')
        );
        
        // Update cache
        if ($result !== false) {
            self::$cache[$key] = $value;
        }
        
        return $result !== false;
    }
    
    /**
     * Get multiple settings
     */
    public static function get_settings($keys) {
        $settings = array();
        
        foreach ($keys as $key) {
            $settings[$key] = self::get_setting($key);
        }
        
        return $settings;
    }
    
    /**
     * Get all settings
     */
    public static function get_all_settings() {
        global $wpdb;
        
        // Prepared statement ensures query safety and future-proofs against injection even
        // when the current query uses only static values.
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT setting_key, setting_value FROM " . YRR_SETTINGS_TABLE . " WHERE autoload = %d",
                1
            ),
            ARRAY_A
        );
        
        $settings = array();
        
        foreach ($results as $row) {
            $settings[$row['setting_key']] = maybe_unserialize($row['setting_value']);
        }
        
        // Update cache
        self::$cache = array_merge(self::$cache, $settings);
        
        return $settings;
    }
    
    /**
     * Delete a setting
     */
    public static function delete_setting($key) {
        global $wpdb;
        
        $result = $wpdb->delete(
            YRR_SETTINGS_TABLE,
            array('setting_key' => $key),
            array('%s')
        );
        
        // Remove from cache
        if (isset(self::$cache[$key])) {
            unset(self::$cache[$key]);
        }
        
        return $result !== false;
    }
    
    /**
     * Get restaurant information
     */
    public static function get_restaurant_info() {
        return array(
            'name' => self::get_setting('restaurant_name', get_bloginfo('name')),
            'email' => self::get_setting('restaurant_email', get_option('admin_email')),
            'phone' => self::get_setting('restaurant_phone', ''),
            'address' => self::get_setting('restaurant_address', ''),
            'timezone' => self::get_setting('timezone', get_option('timezone_string') ?: 'UTC')
        );
    }
    
    /**
     * Get booking rules
     */
    public static function get_booking_rules() {
        return array(
            'max_party_size' => self::get_setting('max_party_size', 12),
            'min_party_size' => self::get_setting('min_party_size', 1),
            'slot_duration' => self::get_setting('slot_duration', 60),
            'advance_booking_days' => self::get_setting('advance_booking_days', 30),
            'booking_buffer_hours' => self::get_setting('booking_buffer_hours', 2),
            'edit_cutoff_hours' => self::get_setting('edit_cutoff_hours', 2),
            'auto_confirm' => self::get_setting('auto_confirm', 0)
        );
    }
    
    /**
     * Get email settings
     */
    public static function get_email_settings() {
        return array(
            'enabled' => self::get_setting('email_enabled', 1),
            'from_name' => self::get_setting('email_from_name', get_bloginfo('name')),
            'from_address' => self::get_setting('email_from_address', get_option('admin_email')),
            'confirmation_subject' => self::get_setting('confirmation_email_subject', 'Reservation Confirmation - #{reservation_code}'),
            'reminder_enabled' => self::get_setting('reminder_email_enabled', 1),
            'reminder_hours' => self::get_setting('reminder_email_hours', 24),
            'cancellation_enabled' => self::get_setting('cancellation_email_enabled', 1)
        );
    }
}
