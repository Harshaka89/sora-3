<?php
/**
 * Public Controller for Yenolx Restaurant Reservation System
 *
 * This class manages all public-facing functionality, such as shortcodes
 * for the booking form and customer reservation management.
 *
 * @package YRR/Controllers
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YRR_Public_Controller {

    /**
     * Constructor.
     *
     * Hooks into WordPress to register shortcodes.
     */
    public function __construct() {
        add_shortcode('yrr_booking_form', array($this, 'render_booking_form_shortcode'));
        add_shortcode('yrr_my_reservations', array($this, 'render_my_reservations_shortcode'));
    }

    /**
     * Renders the main booking form via a shortcode.
     *
     * @param array $atts Shortcode attributes (e.g., location_id).
     * @return string The HTML content of the booking form.
     */
    public function render_booking_form_shortcode($atts) {
        // Enqueue public scripts and styles specifically when the shortcode is used
        wp_enqueue_style('yrr-public-css');
        wp_enqueue_script('yrr-public-js');

        // Extract attributes with defaults
        $atts = shortcode_atts(array(
            'location_id' => null,
        ), $atts, 'yrr_booking_form');

        // Start output buffering to capture the HTML
        ob_start();

        // Load the view file
        $this->load_public_view('booking-form', ['location_id' => $atts['location_id']]);
        
        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Renders the "My Reservations" portal for customers.
     *
     * @param array $atts Shortcode attributes.
     * @return string The HTML content of the customer portal.
     */
    public function render_my_reservations_shortcode($atts) {
        ob_start();
        $this->load_public_view('my-reservations');
        return ob_get_clean();
    }

    /**
     * Helper function to load a view file from the public views directory.
     *
     * @param string $view_name The name of the view file (without .php).
     * @param array  $data      Data to be passed to the view file.
     */
    private function load_public_view($view_name, $data = array()) {
        // Make data variables available to the view file
        extract($data);

        $view_path = YRR_PLUGIN_PATH . 'views/public/' . $view_name . '.php';

        if (file_exists($view_path)) {
            include $view_path;
        } else {
            echo '<p>Error: The required view file ' . esc_html($view_name) . '.php could not be found.</p>';
        }
    }
}
