<?php
/**
 * Reservation Model for Yenolx Restaurant Reservation System
 *
 * This class handles all data operations for reservations.
 *
 * @package YRR/Models
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YRR_Reservation_Model {

    /**
     * Get a single reservation by its ID.
     *
     * @param int $id The ID of the reservation.
     * @return object|null The reservation object, or null if not found.
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table_name = YRR_RESERVATIONS_TABLE;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    /**
     * Get all reservations with filtering, sorting, and pagination.
     *
     * @param array $args Arguments for filtering and pagination.
     * @return array An array of reservation objects.
     */
    public static function get_all($args = array()) {
        global $wpdb;
        $reservations_table = YRR_RESERVATIONS_TABLE;
        $tables_table = YRR_TABLES_TABLE;

        $defaults = array(
            'limit'       => 20,
            'offset'      => 0,
            'status'      => '',
            'search'      => '',
            'location_id' => null,
            'orderby'     => 'reservation_date',
            'order'       => 'DESC',
        );
        $args = wp_parse_args($args, $defaults);

        $where_clauses = array("1=1");
        $where_values = array();

        if (!empty($args['status'])) {
            $where_clauses[] = "r.status = %s";
            $where_values[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_clauses[] = "(r.customer_name LIKE %s OR r.customer_email LIKE %s OR r.reservation_code LIKE %s)";
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($args['location_id'])) {
            $where_clauses[] = "r.location_id = %d";
            $where_values[] = $args['location_id'];
        }

        $where_sql = implode(' AND ', $where_clauses);

        $sql = $wpdb->prepare(
            "SELECT r.*, t.table_number FROM $reservations_table r LEFT JOIN $tables_table t ON r.table_id = t.id WHERE $where_sql ORDER BY r.{$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            array_merge($where_values, array($args['limit'], $args['offset']))
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Get the total count of reservations for pagination, with filters.
     *
     * @param array $filters Filtering arguments.
     * @return int The total number of reservations.
     */
    public static function get_total_count($filters = array()) {
        global $wpdb;
        $table_name = YRR_RESERVATIONS_TABLE;

        $where_clauses = array("1=1");
        $where_values = array();
        
        if (!empty($filters['status'])) {
            $where_clauses[] = "status = %s";
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_clauses[] = "(customer_name LIKE %s OR customer_email LIKE %s)";
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $where_sql", $where_values);

        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Get dashboard statistics.
     *
     * @param int|null $location_id Optional location ID to filter stats.
     * @return array An array of dashboard statistics.
     */
    public static function get_dashboard_stats($location_id = null) {
        global $wpdb;
        $table_name = YRR_RESERVATIONS_TABLE;
        $today = current_time('Y-m-d');
        
        $stats = array();
        $location_sql = $location_id ? $wpdb->prepare(" AND location_id = %d", $location_id) : '';

        $stats['today'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE reservation_date = %s" . $location_sql, $today));
        $stats['today_guests'] = (int) $wpdb->get_var($wpdb->prepare("SELECT SUM(party_size) FROM $table_name WHERE reservation_date = %s AND status = 'confirmed'" . $location_sql, $today));
        $stats['pending'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'" . $location_sql);
        $stats['confirmed'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE status = 'confirmed' AND reservation_date = %s" . $location_sql, $today));

        return $stats;
    }

    /**
     * Create a new reservation.
     *
     * @param array $data The data for the new reservation.
     * @return int|false The new reservation ID on success, false on failure.
     */
    public static function create($data) {
        global $wpdb;
        $table_name = YRR_RESERVATIONS_TABLE;
        
        $data['created_at'] = current_time('mysql');
        $data['reservation_code'] = self::generate_reservation_code();

        $result = $wpdb->insert($table_name, $data);

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update an existing reservation.
     *
     * @param int   $id   The ID of the reservation to update.
     * @param array $data The new data for the reservation.
     * @return bool True on success, false on failure.
     */
    public static function update($id, $data) {
        global $wpdb;
        $table_name = YRR_RESERVATIONS_TABLE;
        return $wpdb->update($table_name, $data, array('id' => $id));
    }

    /**
     * Delete a reservation.
     *
     * @param int $id The ID of the reservation to delete.
     * @return bool True on success, false on failure.
     */
    public static function delete($id) {
        global $wpdb;
        $table_name = YRR_RESERVATIONS_TABLE;
        return $wpdb->delete($table_name, array('id' => $id));
    }
    
    /**
     * Generate a unique reservation code.
     *
     * @return string The unique reservation code.
     */
    private static function generate_reservation_code() {
        global $wpdb;
        $table_name = YRR_RESERVATIONS_TABLE;
        
        do {
            $code = 'YRR-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE reservation_code = %s", $code));
        } while ($exists);

        return $code;
    }
}
