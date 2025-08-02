<?php
/**
 * Admin Controller - Handles all admin interface logic and page rendering
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}




class YRR_Admin_Controller {
    
  public function __construct() {
        // ... other hooks ...
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
   
        // ... existing hooks ...
    //add_action('admin_init', array($this, 'yrr_register_settings'));
    // ... other hooks ...
    }

   
    // Phone, Currency, Address, etc. — repeat add_settings_field with adapted label, type, and field name.


    public function enqueue_admin_assets($hook) {
        // error_log($hook); // Uncomment for debugging the right hook
        if (strpos($hook, 'yrr-calendar') === false) {
            return;
        }
        wp_enqueue_style(
            'yrr-calendar-admin',
            YRR_PLUGIN_URL . 'assets/css/admin-calendar.css',
            array(),
            YRR_VERSION
        );
        wp_enqueue_script(
            'yrr-calendar-admin',
            YRR_PLUGIN_URL . 'assets/js/admin-calendar.js',
            array('jquery'),
            YRR_VERSION,
            true
        );
    }
    
    /**
     * Register admin menu pages
     */
    public function register_admin_menu() {
        // Main Dashboard Page
        add_menu_page(
            __('Reservations', 'yrr'),
            __('Reservations', 'yrr'),
            'manage_options',
            'yrr-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Dashboard Submenu
        add_submenu_page(
            'yrr-dashboard',
            __('Dashboard', 'yrr'),
            __('Dashboard', 'yrr'),
            'manage_options',
            'yrr-dashboard',
            array($this, 'dashboard_page')
        );
        
        // All Reservations Page
        add_submenu_page(
            'yrr-dashboard',
            __('All Reservations', 'yrr'),
            __('All Reservations', 'yrr'),
            'manage_options',
            'yrr-reservations',
            array($this, 'reservations_page')
        );
        
        // Calendar Page
        add_submenu_page(
            'yrr-dashboard',
            __('Calendar View', 'yrr'),
            __('📅 Calendar', 'yrr'),
            'manage_options',
            'yrr-calendar',
            array($this, 'calendar_page')
        );
        
        // Tables Management
        add_submenu_page(
            'yrr-dashboard',
            __('Tables', 'yrr'),
            __('Tables', 'yrr'),
            'manage_options',
            'yrr-tables',
            array($this, 'tables_page')
        );
        
        // Settings Page
        add_submenu_page(
            'yrr-dashboard',
            __('Settings', 'yrr'),
            __('Settings', 'yrr'),
            'manage_options',
            'yrr-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Display dashboard page
     */
    public function dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        include YRR_PLUGIN_PATH . 'views/admin/dashboard.php';
    }
    
    /**
     * Display all reservations page
     */
    public function reservations_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        include YRR_PLUGIN_PATH . 'views/admin/reservations.php';
    }
    
    /**
     * Display calendar page
     */
    public function calendar_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        include YRR_PLUGIN_PATH . 'views/admin/calendar.php';
    }
    
    /**
     * Display tables page
     */
    public function tables_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . __('Tables Management', 'yrr') . '</h1>';
        echo '<p>' . __('Table management interface coming soon...', 'yrr') . '</p>';
        echo '</div>';
    }
    
    /**
     * Display settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . __('Settings', 'yrr') . '</h1>';
        echo '<p>' . __('Settings interface coming soon...', 'yrr') . '</p>';
        echo '</div>';
    }
    
    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'location_id' => 1,
            'show_location_selector' => 'false',
            'theme' => 'default',
            'title' => __('Make a Reservation', 'yrr')
        ), $atts, 'yrr_booking_form');
        
        ob_start();
        
        // Get available locations
        $locations = YRR_Locations_Model::get_all(true);
        $selected_location = YRR_Locations_Model::get_by_id($atts['location_id']);
        
        // Fallback to first location if none found
        if (!$selected_location && !empty($locations)) {
            $selected_location = $locations[0];
        }
        
        // Create a default location object if none exist
        if (!$selected_location) {
            $selected_location = (object) array(
                'id' => 1,
                'name' => 'Main Location'
            );
        }
        
        ?>
        <div class="yrr-public-booking-form" data-location-id="<?php echo esc_attr($selected_location->id); ?>">
            
            <?php if ($atts['title']): ?>
            <h3 class="yrr-form-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <form id="yrr-booking-form" class="yrr-booking-form" method="post">
                <?php wp_nonce_field('yrr_public_booking', 'yrr_booking_nonce'); ?>
                <input type="hidden" name="action" value="yrr_create_public_reservation">
                <input type="hidden" name="location_id" value="<?php echo esc_attr($selected_location->id); ?>">
                
                <!-- Step 1: Party Details -->
                <div class="yrr-form-step yrr-step-active" data-step="1">
                    <h4><?php _e('Party Details', 'yrr'); ?></h4>
                    
                    <div class="yrr-form-row">
                        <div class="yrr-form-field">
                            <label for="party_size"><?php _e('Party Size', 'yrr'); ?> *</label>
                            <select id="party_size" name="party_size" required class="yrr-form-control">
                                <option value=""><?php _e('Select party size...', 'yrr'); ?></option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>">
                                        <?php echo $i; ?> <?php echo $i === 1 ? __('guest', 'yrr') : __('guests', 'yrr'); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="yrr-form-field">
                            <label for="reservation_date"><?php _e('Preferred Date', 'yrr'); ?> *</label>
                            <input type="date" id="reservation_date" name="reservation_date" required 
                                   class="yrr-form-control" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="yrr-form-actions">
                        <button type="button" class="yrr-btn yrr-btn-primary" onclick="YRR_Public.nextStep(2)">
                            <?php _e('Choose Time', 'yrr'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Add more form steps as needed -->
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * My reservations shortcode  
     */
    public function my_reservations_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('My Reservations', 'yrr'),
            'show_search' => 'true'
        ), $atts, 'yrr_my_reservations');
        
        ob_start();
        ?>
        <div class="yrr-my-reservations">
            <?php if ($atts['title']): ?>
            <h3 class="yrr-section-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <div class="yrr-reservation-search">
                <form id="yrr-search-form" class="yrr-search-form">
                    <div class="yrr-form-row">
                        <div class="yrr-form-field">
                            <label for="search_email"><?php _e('Email Address', 'yrr'); ?></label>
                            <input type="email" id="search_email" name="search_email" required class="yrr-form-control">
                        </div>
                        <div class="yrr-form-field">
                            <label for="search_code"><?php _e('Reservation Code (Optional)', 'yrr'); ?></label>
                            <input type="text" id="search_code" name="search_code" class="yrr-form-control">
                        </div>
                    </div>
                    <div class="yrr-form-actions">
                        <button type="submit" class="yrr-btn yrr-btn-primary">
                            <?php _e('Find My Reservations', 'yrr'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <div id="yrr-reservations-list" class="yrr-reservations-list">
                <div class="yrr-empty-message">
                    <?php _e('Enter your email address to view your reservations.', 'yrr'); ?>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle various admin actions
        if (isset($_GET['action']) && isset($_GET['page']) && strpos($_GET['page'], 'yrr-') === 0) {
            $action = sanitize_text_field($_GET['action']);
            $this->process_admin_action($action);
        }
    }
    
    /**
     * Handle form submissions
     */
    public function handle_form_submissions() {
        if (!isset($_POST['yrr_action']) || !wp_verify_nonce($_POST['yrr_nonce'], 'yrr_admin_action')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['yrr_action']);
        $this->process_form_action($action);
    }
    
    /**
     * Process admin actions
     */
    private function process_admin_action($action) {
        switch ($action) {
            case 'delete_reservation':
                $this->delete_reservation();
                break;
                
            case 'confirm_reservation':
                $this->confirm_reservation();
                break;
                
            case 'cancel_reservation':
                $this->cancel_reservation();
                break;
                
            default:
                break;
        }
    }
    
    /**
     * Process form actions
     */
    private function process_form_action($action) {
        switch ($action) {
            case 'create_reservation':
                $this->create_reservation();
                break;
                
            case 'update_reservation':
                $this->update_reservation();
                break;
                
            default:
                break;
        }
    }
    
    /**
     * Create new reservation
     */
    private function create_reservation() {
        $data = array(
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'party_size' => intval($_POST['party_size']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'reservation_time' => sanitize_text_field($_POST['reservation_time']),
            'special_requests' => sanitize_textarea_field($_POST['special_requests']),
            'source' => 'admin'
        );
        
        $result = YRR_Reservation_Model::create($data);
        
        if (is_wp_error($result)) {
            $this->add_admin_notice($result->get_error_message(), 'error');
        } else {
            $this->add_admin_notice(__('Reservation created successfully!', 'yrr'), 'success');
        }
        
        $this->redirect_back();
    }

    /**
     * Update existing reservation
     */
    private function update_reservation() {
        $reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : intval($_POST['id']);

        if (!$reservation_id) {
            $this->add_admin_notice(__('Invalid reservation ID.', 'yrr'), 'error');
            $this->redirect_back();
            return;
        }

        $data = array(
            'customer_name'   => sanitize_text_field($_POST['customer_name'] ?? ''),
            'customer_email'  => sanitize_email($_POST['customer_email'] ?? ''),
            'customer_phone'  => sanitize_text_field($_POST['customer_phone'] ?? ''),
            'party_size'      => intval($_POST['party_size'] ?? 0),
            'reservation_date' => sanitize_text_field($_POST['reservation_date'] ?? ''),
            'reservation_time' => sanitize_text_field($_POST['reservation_time'] ?? ''),
            'special_requests' => sanitize_textarea_field($_POST['special_requests'] ?? '')
        );

        if (isset($_POST['status'])) {
            $data['status'] = sanitize_text_field($_POST['status']);
        }
        if (isset($_POST['table_id']) && $_POST['table_id'] !== '') {
            $data['table_id'] = intval($_POST['table_id']);
        }
        if (isset($_POST['location_id'])) {
            $data['location_id'] = intval($_POST['location_id']);
        }
        if (isset($_POST['source'])) {
            $data['source'] = sanitize_text_field($_POST['source']);
        }

        $required = array('customer_name', 'customer_email', 'customer_phone', 'party_size', 'reservation_date', 'reservation_time');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->add_admin_notice(sprintf(__('Field %s is required.', 'yrr'), $field), 'error');
                $this->redirect_back();
                return;
            }
        }

        $result = YRR_Reservation_Model::update($reservation_id, $data);

        if (is_wp_error($result)) {
            $this->add_admin_notice($result->get_error_message(), 'error');
        } elseif ($result) {
            $this->add_admin_notice(__('Reservation updated successfully!', 'yrr'), 'success');
        } else {
            $this->add_admin_notice(__('Failed to update reservation.', 'yrr'), 'error');
        }

        $this->redirect_back();
    }

    /**
     * Delete reservation
     */
    private function delete_reservation() {
        $reservation_id = intval($_GET['id']);
        
        if (!$reservation_id) {
            $this->add_admin_notice(__('Invalid reservation ID.', 'yrr'), 'error');
            $this->redirect_back();
            return;
        }
        
        $result = YRR_Reservation_Model::delete($reservation_id);
        
        if ($result) {
            $this->add_admin_notice(__('Reservation deleted successfully!', 'yrr'), 'success');
        } else {
            $this->add_admin_notice(__('Failed to delete reservation.', 'yrr'), 'error');
        }
        
        $this->redirect_back();
    }
    
    /**
     * Confirm reservation
     */
    private function confirm_reservation() {
        $reservation_id = intval($_GET['id']);
        
        $result = YRR_Reservation_Model::update_status($reservation_id, 'confirmed');
        
        if ($result) {
            $this->add_admin_notice(__('Reservation confirmed!', 'yrr'), 'success');
        } else {
            $this->add_admin_notice(__('Failed to confirm reservation.', 'yrr'), 'error');
        }
        
        $this->redirect_back();
    }
    
    /**
     * Cancel reservation
     */
    private function cancel_reservation() {
        $reservation_id = intval($_GET['id']);
        
        $result = YRR_Reservation_Model::update_status($reservation_id, 'cancelled');
        
        if ($result) {
            $this->add_admin_notice(__('Reservation cancelled!', 'yrr'), 'success');
        } else {
            $this->add_admin_notice(__('Failed to cancel reservation.', 'yrr'), 'error');
        }
        
        $this->redirect_back();
    }
    
    /**
     * Add admin notice
     */
    private function add_admin_notice($message, $type = 'info') {
        set_transient('yrr_admin_notice', array(
            'message' => $message,
            'type' => $type
        ), 30);
    }
    
    /**
     * Redirect back to previous page
     */
    private function redirect_back() {
        $redirect_url = wp_get_referer();
        
        if (!$redirect_url) {
            $redirect_url = admin_url('admin.php?page=yrr-dashboard');
        }
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Get dashboard data
     */
    public function get_dashboard_data() {
        $stats = YRR_Reservation_Model::get_dashboard_stats();
        $today_reservations = YRR_Reservation_Model::get_all(array(
            'date_from' => current_time('Y-m-d'),
            'date_to' => current_time('Y-m-d'),
            'limit' => 10
        ));
        
        return array(
            'stats' => $stats,
            'today_reservations' => $today_reservations
        );
    }
}
