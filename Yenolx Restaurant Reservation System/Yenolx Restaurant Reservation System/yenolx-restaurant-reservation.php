<?php
/*
Plugin Name: Yenolx Restaurant Reservation System
Plugin URI: https://yenolx.com
Description: Complete restaurant reservation management system with multi-location support, WooCommerce deposits, PWA capabilities, and comprehensive analytics.
Version: 1.6.0
Author: YENOLX
Author URI: https://yenolx.com
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: yrr
Domain Path: /languages
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 8.1
WC requires at least: 9.7
WC tested up to: 9.8
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Plugin constants
define('YRR_VERSION', '1.6.0');
define('YRR_PLUGIN_FILE', __FILE__);
define('YRR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YRR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('YRR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Yenolx Restaurant Reservation System Class
 * 
 * @since 1.6.0
 */
final class YenolxRestaurantReservation {
    
    /**
     * Plugin instance
     * 
     * @var YenolxRestaurantReservation
     */
    private static $instance;
    
    /**
     * Get plugin instance
     * 
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
    
    ////admin settings

    function yrr_setting_input_sanitize($input) {
    foreach($input as &$val) {
        $val = sanitize_text_field($val);
    }
    return $input;
}

    /**
     * Setup plugin constants
     */
    private function setup_constants() {
        // Database table names
        global $wpdb;
        
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


    /**
 * Include required files
 */
private function includes() {
    // Core includes
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
    
    // ADD THIS LINE - AJAX Controller
    $this->include_if_exists('controllers/class-ajax-controller.php');
}

    
    /**
     * Include file if it exists
     */
    private function include_if_exists($file) {
        $full_path = YRR_PLUGIN_PATH . $file;
        if (file_exists($full_path)) {
            require_once $full_path;
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Activation and deactivation
        register_activation_hook(YRR_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(YRR_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('init', array($this, 'init'), 10);
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('admin_notices', array($this, 'admin_notices'));
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        if (class_exists('YRR_Database')) {
            YRR_Database::create_tables();
        }
        
        // Set activation flag
        update_option('yrr_activation_flag', true);
        
        // Set version
        update_option('yrr_version', YRR_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Remove activation flag
        delete_option('yrr_activation_flag');
    }

    /**
     * Initialize plugin
     */
public function init() {
    // Load text domain
    $this->load_textdomain();
    
    // Initialize controllers
    if (class_exists('YRR_Admin_Controller')) {
        new YRR_Admin_Controller();
    }
    
    if (class_exists('YRR_Public_Controller')) {
        $public_controller = new YRR_Public_Controller();
        
        // Register shortcodes
        add_shortcode('yrr_booking_form', array($public_controller, 'booking_form_shortcode'));
        add_shortcode('yrr_my_reservations', array($public_controller, 'my_reservations_shortcode'));
    }
    
    if (class_exists('YRR_Ajax_Controller')) {
        new YRR_Ajax_Controller();
    }
    
    // Show success message after activation
    if (get_option('yrr_activation_flag')) {
        delete_option('yrr_activation_flag');
        add_action('admin_notices', array($this, 'activation_notice'));
    }
}

    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('yrr', false, dirname(YRR_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    /**
 * Add admin menu
 */
public function admin_menu() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Main menu
    add_menu_page(
        __('Restaurant Reservations', 'yrr'),
        __('Reservations', 'yrr'),
        'manage_options',
        'yrr-dashboard',
        array($this, 'dashboard_page'),
        'dashicons-calendar-alt',
        26
    );
    
    // Sub-menu pages
    $submenu_pages = array(
        'dashboard' => __('Dashboard', 'yrr'),
        'reservations' => __('All Reservations', 'yrr'),
        'calendar' => __('📅 Calendar', 'yrr'),           // ADD THIS LINE
        'weekly' => __('Weekly View', 'yrr'),
        'schedule' => __('Table Schedule', 'yrr'),
        'tables' => __('Tables', 'yrr'),
        'hours' => __('Operating Hours', 'yrr'),
        'locations' => __('Locations', 'yrr'),
        'coupons' => __('Coupons', 'yrr'),
        'analytics' => __('Analytics', 'yrr'),
        'settings' => __('Settings', 'yrr')
    );
    
    foreach ($submenu_pages as $slug => $title) {
        add_submenu_page(
            'yrr-dashboard',
            $title,
            $title,
            'manage_options',
            'yrr-' . $slug,
            array($this, $slug . '_page')
        );
    }
}

    
    /**
     * Dashboard page - THIS IS THE KEY METHOD
     */
    public function dashboard_page() {
        // Check if we have the advanced dashboard
        $dashboard_file = YRR_PLUGIN_PATH . 'views/admin/dashboard.php';
        
        if (file_exists($dashboard_file) && class_exists('YRR_Admin_Controller')) {
            // Load the advanced dashboard we built together
            include $dashboard_file;
        } else {
            // Fallback to basic welcome screen with diagnostic info
            ?>
            <div class="wrap">
                <h1><?php _e('Yenolx Restaurant Reservation System v1.6', 'yrr'); ?></h1>
                
                <div class="notice notice-info">
                    <p><strong><?php _e('System Status:', 'yrr'); ?></strong></p>
                    <ul>
                        <li>✅ <?php _e('Main plugin file loaded', 'yrr'); ?></li>
                        <li><?php echo file_exists($dashboard_file) ? '✅' : '❌'; ?> 
                            <?php _e('Dashboard view file', 'yrr'); ?> 
                            <code><?php echo $dashboard_file; ?></code>
                        </li>
                        <li><?php echo class_exists('YRR_Admin_Controller') ? '✅' : '❌'; ?> 
                            <?php _e('Admin Controller class', 'yrr'); ?>
                        </li>
                        <li><?php echo class_exists('YRR_Settings_Model') ? '✅' : '❌'; ?> 
                            <?php _e('Settings Model class', 'yrr'); ?>
                        </li>
                        <li><?php echo class_exists('YRR_Reservation_Model') ? '✅' : '❌'; ?> 
                            <?php _e('Reservation Model class', 'yrr'); ?>
                        </li>
                    </ul>
                </div>
                
                <?php if (!file_exists($dashboard_file)): ?>
                <div class="notice notice-error">
                    <p><strong><?php _e('Missing Dashboard View!', 'yrr'); ?></strong></p>
                    <p><?php _e('The advanced dashboard view file is missing. Please ensure you have created:', 'yrr'); ?></p>
                    <code><?php echo $dashboard_file; ?></code>
                </div>
                <?php endif; ?>
                
                <?php if (!class_exists('YRR_Admin_Controller')): ?>
                <div class="notice notice-error">
                    <p><strong><?php _e('Missing Admin Controller!', 'yrr'); ?></strong></p>
                    <p><?php _e('The admin controller class is not loaded. Please ensure you have created:', 'yrr'); ?></p>
                    <code><?php echo YRR_PLUGIN_PATH . 'controllers/class-admin-controller.php'; ?></code>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <h2><?php _e('Next Steps', 'yrr'); ?></h2>
                    <ol>
                        <li><?php _e('Ensure all files from our step-by-step build are in place', 'yrr'); ?></li>
                        <li><?php _e('Check file permissions are correct', 'yrr'); ?></li>
                        <li><?php _e('Clear any caching plugins', 'yrr'); ?></li>
                        <li><?php _e('Check WordPress debug log for PHP errors', 'yrr'); ?></li>
                    </ol>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Other admin page methods
     */
    public function reservations_page() {
        $this->load_admin_view('reservations', 'All Reservations');
    }
    
    public function weekly_page() {
        $this->load_admin_view('weekly', 'Weekly View');
    }
   
    public function calendar_page() {
    $this->load_admin_view('calendar', 'Calendar View');
}
    
    public function schedule_page() {
        $this->load_admin_view('schedule', 'Table Schedule');
    }
    
    public function tables_page() {
        $this->load_admin_view('tables', 'Tables Management');
    }
    
    public function hours_page() {
        $this->load_admin_view('hours', 'Operating Hours');
    }
    
    public function locations_page() {
        $this->load_admin_view('locations', 'Locations');
    }
    
    public function coupons_page() {
        $this->load_admin_view('coupons', 'Coupons');
    }
    
    public function analytics_page() {
        $this->load_admin_view('analytics', 'Analytics');
    }
    
    public function settings_page() {
        $this->load_admin_view('settings', 'Settings');
    }
    
    /**
     * Load admin view or show placeholder
     */
    private function load_admin_view($view, $title) {
        $file = YRR_PLUGIN_PATH . 'views/admin/' . $view . '.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . esc_html($title) . '</h1>';
            echo '<div class="notice notice-info"><p>' . sprintf(__('The %s view is coming soon!', 'yrr'), $title) . '</p></div>';
            echo '</div>';
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'yrr-') === false) {
            return;
        }
        
        // Styles
        $css_file = YRR_PLUGIN_URL . 'assets/admin.css';
        if (file_exists(YRR_PLUGIN_PATH . 'assets/admin.css')) {
            wp_enqueue_style('yrr-admin', $css_file, array(), YRR_VERSION);
        }
        
        // Scripts
        $js_file = YRR_PLUGIN_URL . 'assets/admin.js';
        if (file_exists(YRR_PLUGIN_PATH . 'assets/admin.js')) {
            wp_enqueue_script('yrr-admin', $js_file, array('jquery'), YRR_VERSION, true);
            
            // Localize script for AJAX
            wp_localize_script('yrr-admin', 'yrr_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('yrr/v1/'),
                'nonce' => wp_create_nonce('yrr_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'yrr'),
                    'loading' => __('Loading...', 'yrr'),
                    'error' => __('An error occurred. Please try again.', 'yrr'),
                    'success' => __('Operation completed successfully.', 'yrr'),
                    'no_slots' => __('No available time slots for the selected date.', 'yrr'),
                    'invalid_coupon' => __('Invalid or expired coupon code.', 'yrr'),
                    'booking_success' => __('Your reservation has been created successfully!', 'yrr')
                )
            ));
        }
    }
    
    /**
     * Show activation notice
     */
    public function activation_notice() {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php _e('Yenolx Restaurant Reservation System activated successfully!', 'yrr'); ?></strong></p>
            <p><?php _e('Go to Reservations → Dashboard to get started.', 'yrr'); ?></p>
        </div>
        <?php
    }
    



    add_action('admin_init', 'yrr_register_core_settings');

function yrr_register_core_settings() {
    // This is the group name for all your settings
    $settings_group = 'yrr_settings_group';
    // This is the page slug where the settings will be displayed
    $page_slug = 'yrr_settings_page';

    // Register each setting you want to save
    register_setting($settings_group, 'yrr_slot_duration');
    register_setting($settings_group, 'yrr_reservation_enabled');
    register_setting($settings_group, 'yrr_location');
    // Add other settings like 'yrr_business_name', 'yrr_max_party_size' here if needed.

    // Add a settings section to group the fields
    add_settings_section(
        'yrr_general_section',
        'General Reservation Settings',
        null, // Optional callback to display a description for the section
        $page_slug
    );

    // Add the individual fields to our new section
    add_settings_field(
        'yrr_slot_duration',
        'Time Slot Duration',
        'yrr_render_slot_duration_field', // This function will render the HTML input
        $page_slug,
        'yrr_general_section'
    );

    add_settings_field(
        'yrr_reservation_enabled',
        'Enable Reservations',
        'yrr_render_reservation_enabled_field',
        $page_slug,
        'yrr_general_section'
    );
    
    add_settings_field(
        'yrr_location',
        'Restaurant Location',
        'yrr_render_location_field',
        $page_slug,
        'yrr_general_section'
    );
}

// --- These functions render the HTML for each field ---

function yrr_render_slot_duration_field() {
    $current_value = get_option('yrr_slot_duration', 60); // Default to 60 minutes if not set
    $options = [
        15 => '15 Minutes',
        20 => '20 Minutes',
        30 => '30 Minutes',
        60 => '1 Hour',
        90 => '1.5 Hours',
        120 => '2 Hours',
        180 => '3 Hours',
    ];
    echo '<select name="yrr_slot_duration">';
    foreach ($options as $value => $label) {
        echo '<option value="' . esc_attr($value) . '" ' . selected($current_value, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">The length of each booking time slot.</p>';
}

function yrr_render_reservation_enabled_field() {
    $current_value = get_option('yrr_reservation_enabled', 1); // Default to enabled
    echo '<label><input type="checkbox" name="yrr_reservation_enabled" value="1" ' . checked($current_value, 1, false) . ' /> ';
    echo 'Allow customers to make new reservations.</label>';
    echo '<p class="description">Uncheck this to temporarily disable all bookings site-wide.</p>';
}

function yrr_render_location_field() {
    $current_value = get_option('yrr_location', '');
    echo '<input type="text" name="yrr_location" value="' . esc_attr($current_value) . '" class="regular-text" placeholder="e.g., Downtown"/>';
    echo '<p class="description">The primary city or area of your restaurant.</p>';
}
    /**
     * Show admin notices
     */
    public function admin_notices() {
        // Check for admin notices
        $admin_notice = get_transient('yrr_admin_notice');
        if ($admin_notice) {
            delete_transient('yrr_admin_notice');
            ?>
            <div class="notice notice-<?php echo esc_attr($admin_notice['type']); ?> is-dismissible">
                <p><?php echo esc_html($admin_notice['message']); ?></p>
            </div>
            <?php
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.1', '<')) {
            ?>
            <div class="notice notice-error">
                <p><strong><?php _e('Yenolx Restaurant Reservation System:', 'yrr'); ?></strong> 
                <?php printf(__('This plugin requires PHP 8.1 or higher. You are running PHP %s.', 'yrr'), PHP_VERSION); ?></p>
            </div>
            <?php
        }
        
        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, '6.6', '<')) {
            ?>
            <div class="notice notice-error">
                <p><strong><?php _e('Yenolx Restaurant Reservation System:', 'yrr'); ?></strong> 
                <?php printf(__('This plugin requires WordPress 6.6 or higher. You are running WordPress %s.', 'yrr'), $wp_version); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * Get plugin version
     */
    public function get_version() {
        return YRR_VERSION;
    }
    
    /**
     * Prevent cloning
     */
    public function __clone() {
        _doing_it_wrong(__FUNCTION__, __('Cloning is forbidden.', 'yrr'), YRR_VERSION);
    }
    
    /**
     * Prevent unserializing
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, __('Unserializing instances is forbidden.', 'yrr'), YRR_VERSION);
    }
}

/**
 * Get main plugin instance
 */
function YRR() {
    return YenolxRestaurantReservation::instance();
}

// Initialize the plugin
YRR();
