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
        add_action('admin_init', array($this, 'register_settings'));
        
        // This hook is designed to handle form submissions posted to admin-post.php
        add_action('admin_post_yrr_add_table', array($this, 'save_table'));
        add_action('admin_post_yrr_edit_table', array($this, 'save_table'));
        add_action('admin_post_yrr_save_hours', array($this, 'save_hours'));
        // Add similar lines for locations, coupons, etc.
    }

    /**
     * Registers all plugin settings.
     */
    public function register_settings() {
        // ... all register_setting code from before ...
    }

    /**
     * Handles saving a table (both create and update).
     */
    public function save_table() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }

        $action = $_POST['action'];
        check_admin_referer($action . '_nonce');

        $data = [
            'table_number' => sanitize_text_field($_POST['table_number']),
            'capacity'     => absint($_POST['capacity']),
            'location'     => sanitize_text_field($_POST['location']),
        ];

        if ($action === 'yrr_edit_table') {
            $table_id = absint($_POST['table_id']);
            YRR_Tables_Model::update($table_id, $data);
        } else {
            YRR_Tables_Model::create($data);
        }

        // Redirect back to the tables page with a success message
        wp_redirect(admin_url('admin.php?page=yrr-tables&success=1'));
        exit;
    }

    /**
     * Handles saving operating hours.
     */
    public function save_hours() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        
        check_admin_referer('yrr_save_hours_nonce', 'yrr_hours_nonce');

        foreach ($_POST['hours'] as $day => $data) {
            YRR_Hours_Model::update_hours(absint($data['id']), [
                'is_closed'   => absint($data['is_closed']),
                'open_time'   => sanitize_text_field($data['open_time']),
                'close_time'  => sanitize_text_field($data['close_time']),
                'break_start' => sanitize_text_field($data['break_start']),
                'break_end'   => sanitize_text_field($data['break_end'])
            ]);
        }
        
        wp_redirect(admin_url('admin.php?page=yrr-hours&success=1'));
        exit;
    }
    
    // ... other controller methods like render_field, sanitize_settings, etc.
}
