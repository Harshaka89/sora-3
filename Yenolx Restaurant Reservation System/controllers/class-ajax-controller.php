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
        add_action('wp_ajax_nopriv_yrr_create_reservation', array($this, 'create_reservation'));
        add_action('wp_ajax_yrr_create_reservation', array($this, 'create_reservation'));
        add_action('wp_ajax_nopriv_yrr_get_my_reservations', array($this, 'get_my_reservations'));
        add_action('wp_ajax_yrr_get_my_reservations', array($this, 'get_my_reservations'));

        // Admin AJAX actions
        add_action('wp_ajax_yrr_update_reservation_time', array($this, 'update_reservation_time'));
    }

    /**
     * [NEW] Fetches all reservations for a given customer email.
     */
    public function get_my_reservations() {
        check_ajax_referer('yrr_public_nonce', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (empty($email)) {
            wp_send_json_error(array('message' => 'Email address is required.'));
            return;
        }

        // Use our Reservation Model to get the data
        $reservations = YRR_Reservation_Model::get_by_customer_email($email);

        // We only want to send back a safe subset of data
        $formatted_reservations = array();
        foreach ($reservations as $booking) {
            $formatted_reservations[] = [
                'reservation_date' => date('l, F j, Y', strtotime($booking->reservation_date)),
                'reservation_time' => date('g:i A', strtotime($booking->reservation_time)),
                'party_size'       => $booking->party_size,
                'status'           => ucfirst($booking->status),
            ];
        }

        wp_send_json_success($formatted_reservations);
    }
    
    /**
     * Creates a reservation from the public booking form and sends a confirmation email.
     */
    public function create_reservation() {
        // ... existing code for creating a reservation
    }

    /**
     * Handles the drag-and-drop update from the admin calendar.
     */
    public function update_reservation_time() {
        // ... existing code for updating reservation time
    }
}
