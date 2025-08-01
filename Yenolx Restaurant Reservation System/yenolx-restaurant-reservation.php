<?php
/*
Plugin Name: Yenolx Restaurant Reservation System
Plugin URI: https://yenolx.com
Description: Complete restaurant reservation management system with MVC architecture, multi-location support, and advanced booking controls.
Version: 1.6.0
Author: YENOLX
Author URI: https://yenolx.com
License: GPL-2.0+
Text Domain: yrr
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * Main Yenolx Restaurant Reservation System Class
 *
 * @since 1.6.0
 */
final class YenolxRestaurantReservation {

    /**
     * Plugin instance
     * @var YenolxRestaurantReservation
     */
    private static $instance;

    /**
     * Get plugin instance using the Singleton pattern
     * @return YenolxRestaurantReservation
     */
    public static function instance() {
        if (!isset(self::$instance) && !(self::$instance instanceof YenolxRestaurantReservation)) {
            self::$instance = new YenolxRestaurantReservation();
            self::$instance->setup_constants();
            self::$instance->includes();
            self::$instance->setup_hooks();
        }
        return self::$instance;
    }

    /**
     * Setup plugin constants
     */
    private function setup_constants() {
        global $wpdb;
        define('YRR_VERSION', '1.6.0');
        define('YRR_PLUGIN_FILE', __FILE__);
        define('YRR_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('YRR_PLUGIN_PATH', plugin_dir_path(__FILE__));
        define('YRR_PLUGIN_BASENAME', plugin_basename(__FILE__));

        // Define database table names
        define('YRR_RESERVATIONS_TABLE', $wpdb->prefix . 'yrr_reservations');
        define('YRR_TABLES_TABLE', $wpdb->prefix . 'yrr_tables');
        define('YRR_HOURS_TABLE', $wpdb->prefix . 'yrr_operating_hours');
        define('YRR_SETTINGS_TABLE', $wpdb->prefix . 'yrr_settings');
        define('YRR_COUPONS_TABLE', $wpdb->prefix . 'yrr_coupons');
        define('YRR_LOCATIONS_TABLE', $wpdb->prefix . 'yrr_locations');
        define('YRR_POINTS_TABLE', $wpdb->prefix . 'yrr_points');
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core
        $this->include_if_exists('includes/class-database.php');
        $this->include_if_exists('includes/class-installer.php');

        // Models
        $this->include_if_exists('models/class-settings-model.php');
        $this->include_if_exists('models/class-reservation-model.php');
        $this->include_if_exists('models/class-tables-model.php');
        $this->include_if_exists('models/class-hours-model.php');
        $this->include_if_exists('models/class-locations-model.php');
        $this->include_if_exists('models/class-coupons-model.php');
        $this->include_if_exists('models/class-points-model.php');

        // Controllers
        $this->include_if_exists('controllers/class-admin-controller.php');
        $this->include_if_exists('controllers/class-public-controller.php');
        $this->include_if_exists('controllers/class-ajax-controller.php');
    }

    /**
     * Helper to include a file if it exists
     */
    private function include_if_exists($file) {
        $full_path = YRR_PLUGIN_PATH . $file;
        if (file_exists($full_path)) {
            require_once $full_path;
        }
    }

    /**
     * Setup all WordPress hooks
     */
    private function setup_hooks() {
        register_activation_hook(YRR_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(YRR_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('init', array($this, 'init'), 10);
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('wp_enqueue_scripts', array($this, 'public_scripts'));
            add_action('admin_notices', array($this, 'admin_notices'));
        }
    }

    /**
     * Plugin activation tasks
     */
    public function activate() {
        if (class_exists('YRR_Database')) {
            YRR_Database::create_tables();
        }
        update_option('yrr_activation_flag', true);
        update_option('yrr_version', YRR_VERSION);
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation tasks
     */
    public function deactivate() {
        flush_rewrite_rules();
        delete_option('yrr_activation_flag');
    }

    /**
     * Initialize the plugin components
     */
    public function init() {
        $this->load_textdomain();

        if (class_exists('YRR_Admin_Controller')) new YRR_Admin_Controller();
        if (class_exists('YRR_Public_Controller')) new YRR_Public_Controller();
        if (class_exists('YRR_Ajax_Controller')) new YRR_Ajax_Controller();

        // Show success message after activation
        if (get_option('yrr_activation_flag')) {
            delete_option('yrr_activation_flag');
            add_action('admin_notices', array($this, 'activation_notice'));
        }
    }

    /**
     * Load plugin textdomain for translation
     */
    public function load_textdomain() {
        load_plugin_textdomain('yrr', false, dirname(YRR_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Create the admin menu and sub-menus
     */
    public function admin_menu() {
        if (!current_user_can('manage_options')) return;

        add_menu_page(__('Restaurant Reservations', 'yrr'), __('Reservations', 'yrr'), 'manage_options', 'yrr-dashboard', array($this, 'dashboard_page'), 'dashicons-calendar-alt', 26);

        $submenu_pages = array(
            'dashboard'    => __('Dashboard', 'yrr'),
            'reservations' => __('All Reservations', 'yrr'),
            'calendar'     => __('📅 Calendar', 'yrr'),
            'schedule'     => __('Table Schedule', 'yrr'),
            'tables'       => __('Tables', 'yrr'),
            'hours'        => __('Operating Hours', 'yrr'),
            'locations'    => __('Locations', 'yrr'),
            'coupons'      => __('Coupons', 'yrr'),
            'analytics'    => __('Analytics', 'yrr'),
            'settings'     => __('Settings', 'yrr')
        );

        foreach ($submenu_pages as $slug => $title) {
            $hook = add_submenu_page('yrr-dashboard', $title, $title, 'manage_options', 'yrr-' . $slug, array($this, $slug . '_page'));
        }
    }

    /**
     * Renders the dashboard page and loads its data.
     */
    public function dashboard_page() {
        $stats = [];
        if (class_exists('YRR_Reservation_Model')) {
            $stats = YRR_Reservation_Model::get_dashboard_stats();
        }
        $this->load_admin_view('dashboard', 'Dashboard', ['stats' => $stats]);
    }

    /**
     * Dynamically loads the view for each admin page.
     */
    public function __call($name, $arguments) {
        if (strpos($name, '_page') !== false) {
            $view = str_replace('_page', '', $name);
            $this->load_admin_view($view, ucfirst($view));
        }
    }
    
    /**
     * Load admin view file or show a placeholder.
     */
    private function load_admin_view($view, $title, $data = array()) {
        extract($data);
        $file = YRR_PLUGIN_PATH . 'views/admin/' . $view . '.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html($title) . '</h1><div class="notice notice-info"><p>' . sprintf(__('The view file for %s is missing. Please create it at %s.', 'yrr'), '<code>' . $title . '</code>', '<code>' . $file . '</code>') . '</p></div></div>';
        }
    }
    
    /**
     * Enqueue admin scripts and styles.
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'yrr-') === false) return;
        wp_enqueue_style('yrr-admin', YRR_PLUGIN_URL . 'assets/css/admin.css', array(), YRR_VERSION);
        wp_enqueue_script('yrr-admin', YRR_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), YRR_VERSION, true);
        wp_localize_script('yrr-admin', 'yrr_admin_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('yrr_admin_nonce')));
    }

    /**
     * Enqueue public scripts and styles.
     */
    public function public_scripts() {
        wp_enqueue_style('yrr-public', YRR_PLUGIN_URL . 'assets/css/public.css', array(), YRR_VERSION);
        wp_enqueue_script('yrr-public', YRR_PLUGIN_URL . 'assets/js/public.js', array('jquery'), YRR_VERSION, true);
        wp_localize_script('yrr-public', 'yrr_public_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('yrr_public_nonce')));
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        if (version_compare(PHP_VERSION, '8.1', '<')) {
            echo '<div class="notice notice-error"><p><strong>Yenolx Reservations:</strong> ' . sprintf(__('This plugin requires PHP 8.1 or higher. You are running PHP %s.', 'yrr'), PHP_VERSION) . '</p></div>';
        }
        global $wp_version;
        if (version_compare($wp_version, '6.6', '<')) {
            echo '<div class="notice notice-error"><p><strong>Yenolx Reservations:</strong> ' . sprintf(__('This plugin requires WordPress 6.6 or higher. You are running WordPress %s.', 'yrr'), $wp_version) . '</p></div>';
        }
    }

    /**
     * Show success message on activation
     */
    public function activation_notice() {
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Yenolx Restaurant Reservation System activated successfully!', 'yrr') . '</strong> ' . __('Go to Reservations → Dashboard to get started.', 'yrr') . '</p></div>';
    }

    // Prevent cloning and unserializing
    public function __clone() { _doing_it_wrong(__FUNCTION__, 'Cloning is forbidden.', YRR_VERSION); }
    public function __wakeup() { _doing_it_wrong(__FUNCTION__, 'Unserializing instances is forbidden.', YRR_VERSION); }

} // ** THIS IS THE IMPORTANT CLOSING BRACE THAT WAS LIKELY MISSING **

/**
 * Get the main plugin instance
 * @return YenolxRestaurantReservation
 */
function YRR() {
    return YenolxRestaurantReservation::instance();
}

// Initialize The Plugin
YRR();
