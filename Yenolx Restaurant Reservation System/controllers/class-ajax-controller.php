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

        // Admin AJAX actions
        add_action('wp_ajax_yrr_update_reservation_time', array($this, 'update_reservation_time'));
    }

    /**
     * Creates a reservation from the public booking form and sends a confirmation email.
     */
    public function create_reservation() {
        check_ajax_referer('yrr_public_nonce', 'nonce');

        // Sanitize and prepare data from the form submission
        $data = [
            'customer_name'    => sanitize_text_field($_POST['customer_name']),
            'customer_email'   => sanitize_email($_POST['customer_email']),
            'party_size'       => intval($_POST['party_size']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'reservation_time' => sanitize_text_field($_POST['reservation_time']),
            'special_requests' => sanitize_textarea_field($_POST['special_requests']),
            // Add other fields as necessary
        ];

        // Create the reservation using our model
        $reservation_id = YRR_Reservation_Model::create($data);

        if (is_wp_error($reservation_id)) {
            wp_send_json_error(['message' => $reservation_id->get_error_message()]);
            return;
        }

        // Send the confirmation email
        $this->send_confirmation_email($reservation_id);

        wp_send_json_success(['message' => 'Reservation created successfully!']);
    }

    /**
     * [NEW] Sends a confirmation email to the customer.
     *
     * @param int $reservation_id The ID of the reservation.
     */
    private function send_confirmation_email($reservation_id) {
        $reservation = YRR_Reservation_Model::get_by_id($reservation_id);
        if (!$reservation) {
            return;
        }

        $restaurant_info = YRR_Settings_Model::get_restaurant_info();
        $to = $reservation->customer_email;
        $subject = "Your Reservation at " . $restaurant_info['name'] . " is Confirmed!";
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $restaurant_info['name'] . ' <' . $restaurant_info['email'] . '>'
        ];

        // Using output buffering to load the email template
        ob_start();
        include(YRR_PLUGIN_PATH . 'views/emails/confirmation-email.php');
        $message = ob_get_clean();

        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Handles the drag-and-drop update from the admin calendar.
     */
    public function update_reservation_time() {
        // Logic for updating reservation time...
    }
}
