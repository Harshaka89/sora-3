<?php
/**
 * AJAX Controller for Yenolx Restaurant Reservation System
 *
 * This class handles all AJAX requests for both the admin and public sides.
 *
 * @package YRR/Controllers
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YRR_Ajax_Controller {

    /**
     * Constructor. Hooks all AJAX actions.
     */
    public function __construct() {
        // Public AJAX actions
        add_action('wp_ajax_nopriv_yrr_get_available_slots', array($this, 'get_available_slots'));
        add_action('wp_ajax_yrr_get_available_slots', array($this, 'get_available_slots'));
        add_action('wp_ajax_nopriv_yrr_create_reservation', array($this, 'create_reservation'));
        add_action('wp_ajax_yrr_create_reservation', array($this, 'create_reservation'));

        // Admin AJAX actions
        add_action('wp_ajax_yrr_get_calendar_reservations', array($this, 'get_calendar_reservations'));
        add_action('wp_ajax_yrr_update_reservation_time', array($this, 'update_reservation_time'));
    }

    /**
     * Fetches available time slots for the public booking form.
     */
    public function get_available_slots() {
        check_ajax_referer('yrr_public_nonce', 'nonce');
        // Logic for getting slots...
        wp_send_json_success(array());
    }

    /**
     * Creates a reservation from the public booking form.
     */
    public function create_reservation() {
        check_ajax_referer('yrr_public_nonce', 'nonce');
        // Logic for creating reservation...
        wp_send_json_success(array('message' => 'Reservation created!'));
    }

    /**
     * Fetches reservations to display on the admin calendar.
     */
    public function get_calendar_reservations() {
        check_ajax_referer('yrr_admin_nonce', 'nonce');
        // Logic to get calendar events...
        wp_send_json_success(array());
    }

    /**
     * Handles the drag-and-drop update from the admin calendar.
     */
    public function update_reservation_time() {
        // 1. Security Check
        check_ajax_referer('yrr_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        // 2. Sanitize Input
        $reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
        $new_date = isset($_POST['new_date']) ? sanitize_text_field($_POST['new_date']) : '';
        $new_time = isset($_POST['new_time']) ? sanitize_text_field($_POST['new_time']) : '';

        if (!$reservation_id || !$new_date || !$new_time) {
            wp_send_json_error(array('message' => 'Invalid data provided.'));
        }

        // 3. Update the Reservation using our Model
        $result = YRR_Reservation_Model::update($reservation_id, array(
            'reservation_date' => $new_date,
            'reservation_time' => $new_time,
        ));

        // 4. Send Response
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to update reservation in the database.'));
        } else {
            wp_send_json_success(array('message' => 'Reservation rescheduled successfully.'));
        }
    }
}
