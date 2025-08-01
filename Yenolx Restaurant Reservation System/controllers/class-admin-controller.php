<?php
/**
 * Admin Controller for Yenolx Restaurant Reservation System
 *
 * This class manages all admin-side pages, registers settings, and handles form submissions.
 *
 * @package YRR/Controllers
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class YRR_Admin_Controller {

    public function __construct() {
        // Register all settings, sections, and fields for the Settings API
        add_action('admin_init', array($this, 'register_settings'));

        // Handle form submissions from various admin pages
        add_action('admin_init', array($this, 'handle_form_submissions'));
    }

    /**
     * Handles all non-settings form submissions (e.g., from Tables, Hours pages).
     */
    public function handle_form_submissions() {
        if (empty($_POST['action']) || !current_user_can('manage_options')) {
            return;
        }

        // Router for different admin form actions
        switch ($_POST['action']) {
            case 'yrr_add_table':
            case 'yrr_edit_table':
                $this->save_table();
                break;
            case 'yrr_save_hours':
                $this->save_hours();
                break;
            case 'yrr_add_location':
            case 'yrr_edit_location':
                $this->save_location();
                break;
            case 'yrr_add_coupon':
            case 'yrr_edit_coupon':
                $this->save_coupon();
                break;
        }
    }

    /**
     * Registers all plugin settings for use with the WordPress Settings API.
     */
    public function register_settings() {
        // General Settings Section
        register_setting('yrr_settings_group_general', 'yrr_settings_general');
        add_settings_section('yrr_general_section', __('Restaurant Information', 'yrr'), null, 'yrr-settings-general');
        add_settings_field('yrr_restaurant_name', __('Restaurant Name', 'yrr'), array($this, 'render_text_field'), 'yrr-settings-general', 'yrr_general_section', ['name' => 'restaurant_name', 'group' => 'general']);
        add_settings_field('yrr_restaurant_email', __('Contact Email', 'yrr'), array($this, 'render_text_field'), 'yrr-settings-general', 'yrr_general_section', ['name' => 'restaurant_email', 'type' => 'email', 'group' => 'general']);

        // Booking Rules Section
        register_setting('yrr_settings_group_booking', 'yrr_settings_booking');
        add_settings_section('yrr_booking_section', __('Booking Rules', 'yrr'), null, 'yrr-settings-booking');
        add_settings_field('yrr_max_party_size', __('Max Party Size', 'yrr'), array($this, 'render_text_field'), 'yrr-settings-booking', 'yrr_booking_section', ['name' => 'max_party_size', 'type' => 'number', 'group' => 'booking']);
        add_settings_field('yrr_slot_duration', __('Slot Duration (minutes)', 'yrr'), array($this, 'render_text_field'), 'yrr-settings-booking', 'yrr_booking_section', ['name' => 'slot_duration', 'type' => 'number', 'group' => 'booking']);
        add_settings_field('yrr_reservations_enabled', __('Enable Reservations', 'yrr'), array($this, 'render_checkbox_field'), 'yrr-settings-booking', 'yrr_booking_section', ['name' => 'reservations_enabled', 'group' => 'booking']);
    }

    // --- RENDER METHODS FOR SETTINGS API ---

    public function render_text_field($args) {
        $options = get_option('yrr_settings_' . $args['group']);
        $value = isset($options[$args['name']]) ? esc_attr($options[$args['name']]) : '';
        $type = isset($args['type']) ? $args['type'] : 'text';
        echo "<input type='{$type}' name='yrr_settings_{$args['group']}[{$args['name']}]' value='{$value}' class='regular-text' />";
    }

    public function render_checkbox_field($args) {
        $options = get_option('yrr_settings_' . $args['group']);
        $checked = isset($options[$args['name']]) ? checked(1, $options[$args['name']], false) : '';
        echo "<input type='checkbox' name='yrr_settings_{$args['group']}[{$args['name']}]' value='1' {$checked} />";
    }

    // --- SAVE METHODS FOR FORM SUBMISSIONS ---

    private function save_table() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'yrr_' . $_POST['action'] . '_nonce')) return;
        
        $data = [
            'table_number' => sanitize_text_field($_POST['table_number']),
            'capacity'     => intval($_POST['capacity']),
            'location'     => sanitize_text_field($_POST['location']),
        ];

        if ($_POST['action'] === 'yrr_edit_table') {
            YRR_Tables_Model::update(intval($_POST['table_id']), $data);
        } else {
            YRR_Tables_Model::create($data);
        }
        wp_redirect(admin_url('admin.php?page=yrr-tables'));
        exit;
    }

    private function save_hours() {
        if (!wp_verify_nonce($_POST['yrr_hours_nonce'], 'yrr_save_hours_nonce')) return;

        foreach ($_POST['hours'] as $day => $data) {
            YRR_Hours_Model::update_hours(intval($data['id']), [
                'is_closed'  => intval($data['is_closed']),
                'open_time'  => sanitize_text_field($data['open_time']),
                'close_time' => sanitize_text_field($data['close_time']),
                'break_start'=> sanitize_text_field($data['break_start']),
                'break_end'  => sanitize_text_field($data['break_end']),
            ]);
        }
        wp_redirect(admin_url('admin.php?page=yrr-hours'));
        exit;
    }

    private function save_location() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'yrr_' . $_POST['action'] . '_nonce')) return;

        $data = [
            'name'    => sanitize_text_field($_POST['name']),
            'address' => sanitize_textarea_field($_POST['address']),
            'phone'   => sanitize_text_field($_POST['phone']),
            'email'   => sanitize_email($_POST['email']),
        ];

        if ($_POST['action'] === 'yrr_edit_location') {
            YRR_Locations_Model::update(intval($_POST['location_id']), $data);
        } else {
            YRR_Locations_Model::create($data);
        }
        wp_redirect(admin_url('admin.php?page=yrr-locations'));
        exit;
    }

    private function save_coupon() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'yrr_' . $_POST['action'] . '_nonce')) return;

        $data = [
            'code'           => sanitize_text_field($_POST['code']),
            'discount_type'  => sanitize_text_field($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'valid_to'       => sanitize_text_field($_POST['valid_to']),
        ];

        if ($_POST['action'] === 'yrr_edit_coupon') {
            YRR_Coupons_Model::update(intval($_POST['coupon_id']), $data);
        } else {
            YRR_Coupons_Model::create($data);
        }
        wp_redirect(admin_url('admin.php?page=yrr-coupons'));
        exit;
    }
}
