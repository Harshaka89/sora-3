<?php
/**
 * Hours Model for Yenolx Restaurant Reservation System
 *
 * This class handles all data operations for operating hours.
 *
 * @package YRR/Models
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YRR_Hours_Model {

    /**
     * Get all operating hours for a location, ordered by day of the week.
     *
     * @param int|null $location_id The ID of the location.
     * @return array An array of operating hour objects.
     */
    public static function get_all($location_id = null) {
        global $wpdb;
        $table_name = YRR_HOURS_TABLE;

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
     * Get the operating hours for a specific day of the week.
     *
     * @param string   $day_of_week The name of the day (e.g., 'Monday').
     * @param int|null $location_id The ID of the location.
     * @return object|null The hours object for the specified day.
     */
    public static function get_hours_for_day($day_of_week, $location_id = 1) {
        global $wpdb;
        $table_name = YRR_HOURS_TABLE;

        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE day_of_week = %s AND location_id = %d",
            $day_of_week,
            $location_id
        );

        return $wpdb->get_row($sql);
    }
    
    /**
     * Update the operating hours for a specific entry by its ID.
     *
     * @param int   $id   The ID of the hours entry to update.
     * @param array $data The new data for the hours entry.
     * @return bool True on success, false on failure.
     */
    public static function update_hours($id, $data) {
        global $wpdb;
        $table_name = YRR_HOURS_TABLE;

        $allowed_fields = ['is_closed', 'open_time', 'close_time', 'break_start', 'break_end'];
        $update_data = array_intersect_key($data, array_flip($allowed_fields));

        if (empty($update_data)) {
            return false;
        }

        return $wpdb->update($table_name, $update_data, array('id' => $id));
    }

    /**
     * Checks if the restaurant is open at a specific day and time.
     *
     * @param string   $day_of_week The name of the day.
     * @param string   $time The time in 'H:i:s' format.
     * @param int      $location_id The ID of the location.
     * @return bool True if open, false otherwise.
     */
    public static function is_open($day_of_week, $time, $location_id = 1) {
        $hours = self::get_hours_for_day($day_of_week, $location_id);

        if (!$hours || $hours->is_closed) {
            return false;
        }

        $current_timestamp = strtotime($time);
        $open_timestamp = strtotime($hours->open_time);
        $close_timestamp = strtotime($hours->close_time);

        // Handle overnight schedules (e.g., closes at 2:00 AM)
        if ($close_timestamp < $open_timestamp) {
            $close_timestamp += 24 * 3600; // Add 24 hours to close time
        }

        // Check if within operating window
        if ($current_timestamp < $open_timestamp || $current_timestamp >= $close_timestamp) {
            return false;
        }

        // Check if within a break period
        if ($hours->break_start && $hours->break_end) {
            $break_start_timestamp = strtotime($hours->break_start);
            $break_end_timestamp = strtotime($hours->break_end);
            if ($current_timestamp >= $break_start_timestamp && $current_timestamp < $break_end_timestamp) {
                return false;
            }
        }

        return true;
    }
}
