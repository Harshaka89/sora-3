<?php
/**
 * Tables Model for Yenolx Restaurant Reservation System
 *
 * This class handles all data operations for tables.
 *
 * @package YRR/Models
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YRR_Tables_Model {

    /**
     * Get a single table by its ID.
     *
     * @param int $id The ID of the table.
     * @return object|null The table object, or null if not found.
     */
    public static function get_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . YRR_TABLES_TABLE . " WHERE id = %d", $id));
    }

    /**
     * Get all tables with optional filters.
     *
     * @param array $args Filtering arguments.
     * @return array An array of table objects.
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $defaults = array(
            'location_id' => null,
            'orderby'     => 'table_number',
            'order'       => 'ASC',
        );
        $args = wp_parse_args($args, $defaults);

        $where_clauses = array("1=1");
        $where_values = array();

        if (!empty($args['location_id'])) {
            $where_clauses[] = "location_id = %d";
            $where_values[] = $args['location_id'];
        }

        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = $wpdb->prepare(
            "SELECT * FROM " . YRR_TABLES_TABLE . " WHERE $where_sql ORDER BY {$args['orderby']} {$args['order']}",
            $where_values
        );

        return $wpdb->get_results($sql);
    }
    
    /**
     * Get the total count of tables for a specific location.
     *
     * @param int $location_id The ID of the location.
     * @return int The number of tables.
     */
    public static function get_count_by_location($location_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . YRR_TABLES_TABLE . " WHERE location_id = %d", $location_id));
    }

    /**
     * Get available tables based on party size and time.
     *
     * @param int    $party_size The number of guests.
     * @param string $date The reservation date.
     * @param string $time The reservation time.
     * @return array An array of available table objects.
     */
    public static function get_available_by_capacity($party_size, $date, $time) {
        global $wpdb;
        $reservations_table = YRR_RESERVATIONS_TABLE;
        
        // Find tables that are already booked for the given date and time
        $booked_tables_sql = $wpdb->prepare(
            "SELECT table_id FROM $reservations_table WHERE reservation_date = %s AND reservation_time = %s AND status IN ('confirmed', 'pending') AND table_id IS NOT NULL",
            $date, $time
        );
        $booked_table_ids = $wpdb->get_col($booked_tables_sql);

        $where_clauses = array("capacity >= %d");
        $where_values = array($party_size);

        if (!empty($booked_table_ids)) {
            $where_clauses[] = "id NOT IN (" . implode(',', array_map('intval', $booked_table_ids)) . ")";
        }

        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = $wpdb->prepare("SELECT * FROM " . YRR_TABLES_TABLE . " WHERE $where_sql ORDER BY capacity ASC", $where_values);

        return $wpdb->get_results($sql);
    }

    /**
     * Create a new table.
     *
     * @param array $data The data for the new table.
     * @return int|false The new table ID on success, false on failure.
     */
    public static function create($data) {
        global $wpdb;
        return $wpdb->insert(YRR_TABLES_TABLE, $data);
    }

    /**
     * Update an existing table.
     *
     * @param int   $id   The ID of the table to update.
     * @param array $data The new data for the table.
     * @return bool True on success, false on failure.
     */
    public static function update($id, $data) {
        global $wpdb;
        return $wpdb->update(YRR_TABLES_TABLE, $data, array('id' => $id));
    }

    /**
     * Delete a table.
     *
     * @param int $id The ID of the table to delete.
     * @return bool True on success, false on failure.
     */
    public static function delete($id) {
        global $wpdb;
        return $wpdb->delete(YRR_TABLES_TABLE, array('id' => $id));
    }
}
