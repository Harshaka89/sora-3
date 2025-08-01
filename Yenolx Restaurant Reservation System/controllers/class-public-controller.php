<?php
/**
 * Public Controller for Yenolx Restaurant Reservation System
 *
 * This class manages all public-facing functionality, such as shortcodes.
 *
 * @package YRR/Controllers
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YRR_Public_Controller {

    /**
     * Constructor. Hooks into WordPress to register shortcodes.
     */
    public function __construct() {
        add_shortcode('yrr_booking_form', array($this, 'render_booking_form_shortcode'));
        add_shortcode('yrr_my_reservations', array($this, 'render_my_reservations_shortcode'));
    }

    /**
     * Renders the main booking form via a shortcode.
     */
    public function render_booking_form_shortcode($atts) {
        wp_enqueue_style('yrr-public-css');
        wp_enqueue_script('yrr-public-js');
        ob_start();
        include(YRR_PLUGIN_PATH . 'views/public/booking-form.php');
        return ob_get_clean();
    }
    
    /**
     * [NEW] Renders the "My Reservations" portal for customers.
     */
    public function render_my_reservations_shortcode($atts) {
        wp_enqueue_style('yrr-public-css');
        wp_enqueue_script('yrr-public-js');
        ob_start();
        include(YRR_PLUGIN_PATH . 'views/public/my-reservations.php');
        return ob_get_clean();
    }
}
