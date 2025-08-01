<?php
/**
 * Settings Model for Yenolx Restaurant Reservation System
 *
 * This class handles all data operations for plugin settings, providing a
 * standardized way to get and set configuration options.
 *
 * @package YRR/Models
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YRR_Settings_Model {

    /**
     * Internal cache for settings to reduce database queries.
     * @var array
     */
    private static $cache = array();

    /**
     * Get a single setting value from the database.
     *
     * @param string $key     The option key to retrieve.
     * @param mixed  $default The default value to return if the key is not found.
     * @return mixed The value of the setting.
     */
    public static function get_setting($key, $default = false) {
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
            self::$cache[$key] = $value; // Store in cache
            return $value;
        }

        return $default;
    }

    /**
     * Set (add or update) a single setting value.
     *
     * @param string $key   The option key to set.
     * @param mixed  $value The value to store.
     * @return bool True on success, false on failure.
     */
    public static function set_setting($key, $value) {
        global $wpdb;

        $result = $wpdb->replace(
            YRR_SETTINGS_TABLE,
            array(
                'setting_key'   => $key,
                'setting_value' => maybe_serialize($value),
            ),
            array('%s', '%s')
        );

        // Update cache
        if ($result !== false) {
            self::$cache[$key] = $value;
        }

        return $result !== false;
    }
    
    /**
     * Get a predefined group of settings related to booking rules.
     *
     * @return array An array of all booking rule settings.
     */
    public static function get_booking_rules() {
        return array(
            'max_party_size'        => self::get_setting('max_party_size', 12),
            'min_party_size'        => self::get_setting('min_party_size', 1),
            'slot_duration'         => self::get_setting('slot_duration', 60),
            'advance_booking_days'  => self::get_setting('advance_booking_days', 30),
            'booking_buffer_hours'  => self::get_setting('booking_buffer_hours', 2),
            'auto_confirm'          => self::get_setting('auto_confirm', 0),
            'reservations_enabled'  => self::get_setting('reservations_enabled', 1),
            'dynamic_pricing_enabled' => self::get_setting('dynamic_pricing_enabled', 0),
        );
    }
    
    /**
     * Get a predefined group of settings related to restaurant info.
     *
     * @return array An array of all restaurant info settings.
     */
    public static function get_restaurant_info() {
        return array(
            'name'      => self::get_setting('restaurant_name', get_bloginfo('name')),
            'email'     => self::get_setting('restaurant_email', get_option('admin_email')),
            'phone'     => self::get_setting('restaurant_phone', ''),
            'address'   => self::get_setting('restaurant_address', ''),
            'timezone'  => self::get_setting('timezone', get_option('timezone_string') ?: 'UTC'),
        );
    }
}
