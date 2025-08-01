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

    /**
     * Constructor. Hooks into WordPress admin actions.
     */
    public function __construct() {
        // Register all settings, sections, and fields for the Settings API
        add_action('admin_init', array($this, 'register_settings'));

        // Handle form submissions from various admin pages (non-Settings API)
        add_action('admin_init', array($this, 'handle_form_submissions'));
    }

    /**
     * Handles all non-settings form submissions (e.g., from Tables, Hours pages).
     * This acts as a router for various admin actions.
     */
    public function handle_form_submissions() {
        // Check for both POST and GET actions
        if (empty($_REQUEST['action']) || !current_user_can('manage_options')) {
            return;
        }

        $action = sanitize_key($_REQUEST['action']);

        // Route to the correct handler based on the submitted form action
        switch ($action) {
            case 'yrr_add_table':
            case 'yrr_edit_table':
                if (check_admin_referer('yrr_' . $action . '_nonce')) {
                    $this->save_table();
                }
                break;
            case 'yrr_save_hours':
                if (check_admin_referer('yrr_save_hours_nonce', 'yrr_hours_nonce')) {
                    $this->save_hours();
                }
                break;
            case 'yrr_add_location':
            case 'yrr_edit_location':
                if (check_admin_referer('yrr_' . $action . '_nonce')) {
                    $this->save_location();
                }
                break;
            case 'yrr_add_coupon':
            case 'yrr_edit_coupon':
                if (check_admin_referer('yrr_' . $action . '_nonce')) {
                    $this->save_coupon();
                }
                break;
            case 'yrr_export_reservations':
                if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'yrr_export_nonce')) {
                    $this->handle_csv_export();
                }
                break;
        }
    }

    /**
     * Handles the logic for exporting reservations to a CSV file.
     */
    private function handle_csv_export() {
        $filters = array(
            'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null,
            'search' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : null,
        );

        $reservations = YRR_Reservation_Model::get_all(array_merge($filters, ['limit' => -1, 'offset' => 0]));

        if (empty($reservations)) {
            wp_die('No reservations to export based on the current filters.');
        }

        $filename = 'reservations-export-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        fputcsv($output, array(
            'Reservation Code', 'Customer Name', 'Customer Email', 'Date', 'Time',
            'Party Size', 'Table Number', 'Status', 'Special Requests'
        ));

        foreach ($reservations as $reservation) {
            fputcsv($output, array(
                $reservation->reservation_code, $reservation->customer_name, $reservation->customer_email,
                $reservation->reservation_date, $reservation->reservation_time, $reservation->party_size,
                $reservation->table_number ?? 'N/A', ucfirst($reservation->status), $reservation->special_requests
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Registers all plugin settings for use with the WordPress Settings API.
     */
    public function register_settings() {
        // General Settings
        register_setting('yrr_settings_group_general', 'yrr_settings_general', array('sanitize_callback' => array($this, 'sanitize_general_settings')));
        add_settings_section('yrr_general_section', __('Restaurant Information', 'yrr'), null, 'yrr-settings-general');
        add_settings_field('yrr_restaurant_name', __('Restaurant Name', 'yrr'), array($this, 'render_text_field'), 'yrr-settings-general', 'yrr_general_section', ['name' => 'restaurant_name', 'group' => 'general', 'placeholder' => get_bloginfo('name')]);
        add_settings_field('yrr_restaurant_email', __('Contact Email', 'yrr'), array($this, 'render_text_field'), 'yrr-settings-general', 'yrr_general_section', ['name' => 'restaurant_email', 'type' => 'email', 'group' => 'general', 'placeholder' => get_option('admin_email')]);

        // Booking Rules Settings
        register_setting('yrr_settings_group_booking', 'yrr_settings_booking', array('sanitize_callback' => array($this, 'sanitize_booking_settings')));
        add_settings_section('yrr_booking_section', __('Booking Rules', 'yrr'), null, 'yrr-settings-booking');
        add_settings_field('yrr_max_party_size', __('Maximum Party Size', 'yrr'), array($this, 'render_text_field'), 'yrr-settings-booking', 'yrr_booking_section', ['name' => 'max_party_size', 'type' => 'number', 'group' => 'booking', 'default' => 12]);
        add_settings_field('yrr_slot_duration', __('Slot Duration (minutes)', 'yrr'), array($this, 'render_text_field'), 'yrr-settings-booking', 'yrr_booking_section', ['name' => 'slot_duration', 'type' => 'number', 'group' => 'booking', 'default' => 60]);
        add_settings_field('yrr_reservations_enabled', __('Enable All Reservations', 'yrr'), array($this, 'render_checkbox_field'), 'yrr-settings-booking', 'yrr_booking_section', ['name' => 'reservations_enabled', 'group' => 'booking', 'default' => 1]);
    }

    // --- RENDER & SANITIZE METHODS ---

    public function render_text_field($args) {
        $options = get_option('yrr_settings_' . $args['group']);
        $value = esc_attr($options[$args['name']] ?? ($args['default'] ?? ''));
        $placeholder = esc_attr($args['placeholder'] ?? '');
        $type = esc_attr($args['type'] ?? 'text');
        echo "<input type='{$type}' name='yrr_settings_{$args['group']}[{$args['name']}]' value='{$value}' class='regular-text' placeholder='{$placeholder}' />";
    }

    public function render_checkbox_field($args) {
        $options = get_option('yrr_settings_' . $args['group']);
        $value = $options[$args['name']] ?? ($args['default'] ?? 0);
        $checked = checked(1, $value, false);
        echo "<input type='checkbox' name='yrr_settings_{$args['group']}[{$args['name']}]' value='1' {$checked} />";
    }

    public function sanitize_general_settings($input) {
        $sanitized_input = [];
        $sanitized_input['restaurant_name'] = sanitize_text_field($input['restaurant_name']);
        $sanitized_input['restaurant_email'] = sanitize_email($input['restaurant_email']);
        return $sanitized_input;
    }

    public function sanitize_booking_settings($input) {
        $sanitized_input = [];
        $sanitized_input['max_party_size'] = absint($input['max_party_size']);
        $sanitized_input['slot_duration'] = absint($input['slot_duration']);
        $sanitized_input['reservations_enabled'] = isset($input['reservations_enabled']) ? 1 : 0;
        return $sanitized_input;
    }

    // --- SAVE METHODS ---

    private function save_table() {
        $data = ['table_number' => sanitize_text_field($_POST['table_number']), 'capacity' => absint($_POST['capacity']), 'location' => sanitize_text_field($_POST['location'])];
        if ('yrr_edit_table' === $_POST['action']) { YRR_Tables_Model::update(absint($_POST['table_id']), $data); } else { YRR_Tables_Model::create($data); }
        wp_redirect(admin_url('admin.php?page=yrr-tables&success=1')); exit;
    }

    private function save_hours() {
        foreach ($_POST['hours'] as $day => $data) {
            YRR_Hours_Model::update_hours(absint($data['id']), ['is_closed' => absint($data['is_closed']), 'open_time' => sanitize_text_field($data['open_time']), 'close_time' => sanitize_text_field($data['close_time']), 'break_start' => sanitize_text_field($data['break_start']), 'break_end' => sanitize_text_field($data['break_end'])]);
        }
        wp_redirect(admin_url('admin.php?page=yrr-hours&success=1')); exit;
    }

    private function save_location() {
        $data = ['name' => sanitize_text_field($_POST['name']), 'address' => sanitize_textarea_field($_POST['address']), 'phone' => sanitize_text_field($_POST['phone']), 'email' => sanitize_email($_POST['email'])];
        if ('yrr_edit_location' === $_POST['action']) { YRR_Locations_Model::update(absint($_POST['location_id']), $data); } else { YRR_Locations_Model::create($data); }
        wp_redirect(admin_url('admin.php?page=yrr-locations&success=1')); exit;
    }

    private function save_coupon() {
        $data = ['code' => sanitize_text_field($_POST['code']), 'discount_type' => sanitize_text_field($_POST['discount_type']), 'discount_value' => floatval($_POST['discount_value']), 'valid_to' => sanitize_text_field($_POST['valid_to'])];
        if ('yrr_edit_coupon' === $_POST['action']) { YRR_Coupons_Model::update(absint($_POST['coupon_id']), $data); } else { YRR_Coupons_Model::create($data); }
        wp_redirect(admin_url('admin.php?page=yrr-coupons&success=1')); exit;
    }
}
