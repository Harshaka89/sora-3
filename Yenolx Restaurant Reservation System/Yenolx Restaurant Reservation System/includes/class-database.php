<?php
/**
 * Database management class for Yenolx Restaurant Reservation System
 * Handles table creation, updates, and data migrations
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

class YRR_Database {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.6.0';
    
    /**
     * Create all database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Get table names
        $reservations_table = YRR_RESERVATIONS_TABLE;
        $tables_table = YRR_TABLES_TABLE;
        $hours_table = YRR_HOURS_TABLE;
        $settings_table = YRR_SETTINGS_TABLE;
        $coupons_table = YRR_COUPONS_TABLE;
        $locations_table = YRR_LOCATIONS_TABLE;
        $points_table = YRR_POINTS_TABLE;
        
        // Reservations table - Complete v1.6 structure
        $reservations_sql = "CREATE TABLE $reservations_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            reservation_code varchar(50) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(20) NOT NULL,
            party_size int(11) NOT NULL DEFAULT 1,
            reservation_date date NOT NULL,
            reservation_time time NOT NULL,
            table_id int(11) DEFAULT NULL,
            location_id int(11) DEFAULT 1,
            status enum('pending','confirmed','cancelled','completed','no_show') DEFAULT 'pending',
            special_requests text,
            notes text,
            original_price decimal(10,2) DEFAULT 0.00,
            discount_amount decimal(10,2) DEFAULT 0.00,
            final_price decimal(10,2) DEFAULT 0.00,
            deposit_amount decimal(10,2) DEFAULT 0.00,
            deposit_paid tinyint(1) DEFAULT 0,
            coupon_code varchar(50) DEFAULT NULL,
            payment_status enum('pending','paid','refunded') DEFAULT 'pending',
            woocommerce_order_id int(11) DEFAULT NULL,
            source varchar(50) DEFAULT 'admin',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY reservation_code (reservation_code),
            KEY reservation_date (reservation_date),
            KEY reservation_time (reservation_time),
            KEY table_id (table_id),
            KEY location_id (location_id),
            KEY status (status),
            KEY customer_email (customer_email),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Tables table - Physical restaurant tables
        $tables_sql = "CREATE TABLE $tables_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            table_number varchar(50) NOT NULL,
            capacity int(11) NOT NULL DEFAULT 2,
            min_capacity int(11) DEFAULT 1,
            location varchar(255) DEFAULT NULL,
            location_id int(11) DEFAULT 1,
            table_type varchar(50) DEFAULT 'standard',
            position_x int(11) DEFAULT 0,
            position_y int(11) DEFAULT 0,
            status enum('available','occupied','maintenance','reserved') DEFAULT 'available',
            shape enum('square','circle','rectangle') DEFAULT 'square',
            color varchar(7) DEFAULT '#2196F3',
            is_active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY table_location_number (table_number, location_id),
            KEY capacity (capacity),
            KEY location_id (location_id),
            KEY status (status),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Operating hours table - Daily schedules
        $hours_sql = "CREATE TABLE $hours_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            location_id int(11) DEFAULT 1,
            day_of_week varchar(20) NOT NULL,
            open_time time NOT NULL DEFAULT '09:00:00',
            close_time time NOT NULL DEFAULT '22:00:00',
            is_closed tinyint(1) DEFAULT 0,
            break_start time DEFAULT NULL,
            break_end time DEFAULT NULL,
            last_seating_time time DEFAULT NULL,
            buffer_minutes int(11) DEFAULT 15,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY location_day (location_id, day_of_week),
            KEY day_of_week (day_of_week),
            KEY is_closed (is_closed)
        ) $charset_collate;";
        
        // Settings table - Global configuration
        $settings_sql = "CREATE TABLE $settings_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            autoload tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key),
            KEY autoload (autoload)
        ) $charset_collate;";
        
        // Coupons table - Discount management
        $coupons_sql = "CREATE TABLE $coupons_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            description text,
            discount_type enum('fixed','percentage') DEFAULT 'percentage',
            discount_value decimal(10,2) NOT NULL DEFAULT 0.00,
            minimum_amount decimal(10,2) DEFAULT 0.00,
            maximum_discount decimal(10,2) DEFAULT NULL,
            usage_limit int(11) DEFAULT NULL,
            usage_count int(11) DEFAULT 0,
            usage_limit_per_customer int(11) DEFAULT 1,
            valid_from datetime DEFAULT NULL,
            valid_to datetime DEFAULT NULL,
            applicable_days varchar(50) DEFAULT 'all',
            applicable_times varchar(100) DEFAULT 'all',
            location_ids text DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_by int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY is_active (is_active),
            KEY valid_from (valid_from),
            KEY valid_to (valid_to),
            KEY usage_count (usage_count)
        ) $charset_collate;";
        
        // Locations table - Multi-location support
        $locations_sql = "CREATE TABLE $locations_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            address text,
            city varchar(100) DEFAULT NULL,
            state varchar(100) DEFAULT NULL,
            postal_code varchar(20) DEFAULT NULL,
            country varchar(100) DEFAULT NULL,
            phone varchar(20) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            website varchar(255) DEFAULT NULL,
            timezone varchar(50) DEFAULT 'UTC',
            currency varchar(3) DEFAULT 'USD',
            currency_symbol varchar(10) DEFAULT '$',
            google_maps_url text,
            description text,
            image_url varchar(500) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY is_active (is_active),
            KEY is_default (is_default),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        // Points table - Loyalty system foundation
        $points_sql = "CREATE TABLE $points_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            customer_email varchar(255) NOT NULL,
            reservation_id int(11) NOT NULL,
            points_earned int(11) DEFAULT 0,
            points_redeemed int(11) DEFAULT 0,
            transaction_type enum('earned','redeemed','expired','adjusted') DEFAULT 'earned',
            description text,
            expires_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_email (customer_email),
            KEY reservation_id (reservation_id),
            KEY transaction_type (transaction_type),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Create tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($reservations_sql);
        dbDelta($tables_sql);
        dbDelta($hours_sql);
        dbDelta($settings_sql);
        dbDelta($coupons_sql);
        dbDelta($locations_sql);
        dbDelta($points_sql);
        
        // Insert default data
        self::insert_default_data();
        
        // Update database version
        update_option('yrr_db_version', self::DB_VERSION);
        
        return true;
    }
    
    /**
     * Insert default data
     */
    private static function insert_default_data() {
        self::insert_default_location();
        self::insert_default_operating_hours();
        self::insert_default_tables();
        self::insert_default_settings();
        self::insert_sample_coupons();
    }
    
    /**
     * Insert default location
     */
    private static function insert_default_location() {
        global $wpdb;
        
        $existing = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . YRR_LOCATIONS_TABLE
        );
        
        if ($existing == 0) {
            $wpdb->insert(
                YRR_LOCATIONS_TABLE,
                array(
                    'name' => get_bloginfo('name') ?: 'Main Restaurant',
                    'slug' => 'main-restaurant',
                    'address' => '',
                    'phone' => '',
                    'email' => get_option('admin_email'),
                    'timezone' => get_option('timezone_string') ?: 'UTC',
                    'currency' => 'USD',
                    'currency_symbol' => '$',
                    'is_active' => 1,
                    'is_default' => 1,
                    'sort_order' => 1
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d')
            );
        }
    }
    
    /**
     * Insert default operating hours
     */
    private static function insert_default_operating_hours() {
        global $wpdb;
        
        $existing = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . YRR_HOURS_TABLE
        );
        
        if ($existing == 0) {
            $default_hours = array(
                array('Monday', '09:00:00', '22:00:00', 0, '14:00:00', '17:00:00', '21:30:00'),
                array('Tuesday', '09:00:00', '22:00:00', 0, '14:00:00', '17:00:00', '21:30:00'),
                array('Wednesday', '09:00:00', '22:00:00', 0, '14:00:00', '17:00:00', '21:30:00'),
                array('Thursday', '09:00:00', '22:00:00', 0, '14:00:00', '17:00:00', '21:30:00'),
                array('Friday', '09:00:00', '23:00:00', 0, '14:00:00', '17:00:00', '22:30:00'),
                array('Saturday', '09:00:00', '23:00:00', 0, '14:00:00', '17:00:00', '22:30:00'),
                array('Sunday', '10:00:00', '21:00:00', 0, null, null, '20:30:00')
            );
            
            foreach ($default_hours as $hour) {
                $wpdb->insert(
                    YRR_HOURS_TABLE,
                    array(
                        'location_id' => 1,
                        'day_of_week' => $hour[0],
                        'open_time' => $hour[1],
                        'close_time' => $hour[2],
                        'is_closed' => $hour[3],
                        'break_start' => $hour[4],
                        'break_end' => $hour[5],
                        'last_seating_time' => $hour[6],
                        'buffer_minutes' => 15
                    ),
                    array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d')
                );
            }
        }
    }
    
    /**
     * Insert default tables
     */
    private static function insert_default_tables() {
        global $wpdb;
        
        $existing = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . YRR_TABLES_TABLE
        );
        
        if ($existing == 0) {
            $default_tables = array(
                array('Table 1', 2, 1, 'Window Side', 'standard', 50, 50, '#2196F3', 'circle'),
                array('Table 2', 4, 2, 'Main Dining', 'standard', 150, 50, '#4CAF50', 'square'),
                array('Table 3', 6, 4, 'Main Dining', 'standard', 250, 50, '#FF9800', 'rectangle'),
                array('Table 4', 8, 6, 'Private Section', 'vip', 350, 50, '#9C27B0', 'rectangle'),
                array('Table 5', 2, 1, 'Bar Area', 'bar', 50, 150, '#F44336', 'circle'),
                array('Table 6', 4, 2, 'Patio', 'outdoor', 150, 150, '#00BCD4', 'square'),
                array('Table 7', 6, 4, 'Main Dining', 'standard', 250, 150, '#795548', 'rectangle'),
                array('Table 8', 10, 8, 'Banquet', 'banquet', 350, 150, '#607D8B', 'rectangle')
            );
            
            foreach ($default_tables as $table) {
                $wpdb->insert(
                    YRR_TABLES_TABLE,
                    array(
                        'table_number' => $table[0],
                        'capacity' => $table[1],
                        'min_capacity' => $table[2],
                        'location' => $table[3],
                        'location_id' => 1,
                        'table_type' => $table[4],
                        'position_x' => $table[5],
                        'position_y' => $table[6],
                        'color' => $table[7],
                        'shape' => $table[8],
                        'status' => 'available',
                        'is_active' => 1
                    ),
                    array('%s', '%d', '%d', '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%d')
                );
            }
        }
    }
    
    /**
     * Insert default settings
     */
    private static function insert_default_settings() {
        $default_settings = array(
            'restaurant_name' => get_bloginfo('name') ?: 'Your Restaurant',
            'restaurant_email' => get_option('admin_email'),
            'restaurant_phone' => '',
            'restaurant_address' => '',
            'max_party_size' => 12,
            'min_party_size' => 1,
            'slot_duration' => 60,
            'advance_booking_days' => 30,
            'booking_buffer_hours' => 2,
            'edit_cutoff_hours' => 2,
            'auto_confirm' => 0,
            'require_phone' => 1,
            'currency_symbol' => '$',
            'currency_code' => 'USD',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'timezone' => get_option('timezone_string') ?: 'UTC',
            'email_enabled' => 1,
            'email_from_name' => get_bloginfo('name'),
            'email_from_address' => get_option('admin_email'),
            'confirmation_email_subject' => 'Reservation Confirmation - #{reservation_code}',
            'reminder_email_enabled' => 1,
            'reminder_email_hours' => 24,
            'cancellation_email_enabled' => 1,
            'google_maps_api_key' => '',
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
            'enable_deposits' => 0,
            'deposit_type' => 'percentage',
            'deposit_amount' => 20,
            'enable_loyalty' => 0,
            'points_per_dollar' => 1,
            'enable_pwa' => 0,
            'pwa_name' => get_bloginfo('name') . ' Reservations',
            'pwa_short_name' => 'Reservations',
            'default_location' => 1,
            'multi_location_enabled' => 0
        );
        
        foreach ($default_settings as $key => $value) {
            self::set_setting($key, $value);
        }
    }
    
    /**
     * Insert sample coupons
     */
    private static function insert_sample_coupons() {
        global $wpdb;
        
        $existing = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . YRR_COUPONS_TABLE
        );
        
        if ($existing == 0) {
            $sample_coupons = array(
                array(
                    'code' => 'WELCOME10',
                    'description' => 'Welcome discount for new customers',
                    'discount_type' => 'percentage',
                    'discount_value' => 10.00,
                    'minimum_amount' => 50.00,
                    'usage_limit' => 100,
                    'usage_limit_per_customer' => 1,
                    'valid_from' => date('Y-m-d H:i:s'),
                    'valid_to' => date('Y-m-d H:i:s', strtotime('+3 months')),
                    'is_active' => 1
                ),
                array(
                    'code' => 'EARLYBIRD',
                    'description' => 'Early bird discount for lunch reservations',
                    'discount_type' => 'fixed',
                    'discount_value' => 15.00,
                    'minimum_amount' => 75.00,
                    'usage_limit' => 50,
                    'usage_limit_per_customer' => 2,
                    'valid_from' => date('Y-m-d H:i:s'),
                    'valid_to' => date('Y-m-d H:i:s', strtotime('+1 month')),
                    'applicable_times' => '11:00-14:00',
                    'is_active' => 1
                )
            );
            
            foreach ($sample_coupons as $coupon) {
                $wpdb->insert(YRR_COUPONS_TABLE, $coupon);
            }
        }
    }
    
    /**
     * Set a setting value
     */
    private static function set_setting($key, $value) {
        global $wpdb;
        
        $wpdb->replace(
            YRR_SETTINGS_TABLE,
            array(
                'setting_key' => $key,
                'setting_value' => maybe_serialize($value),
                'autoload' => 1
            ),
            array('%s', '%s', '%d')
        );
    }
    
    /**
     * Check if database needs update
     */
    public static function needs_update() {
        $current_version = get_option('yrr_db_version', '0.0.0');
        return version_compare($current_version, self::DB_VERSION, '<');
    }
    
    /**
     * Update database if needed
     */
    public static function maybe_update() {
        if (self::needs_update()) {
            self::create_tables();
        }
    }
    
    /**
     * Drop all plugin tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            YRR_POINTS_TABLE,
            YRR_RESERVATIONS_TABLE,
            YRR_COUPONS_TABLE,
            YRR_TABLES_TABLE,
            YRR_HOURS_TABLE,
            YRR_SETTINGS_TABLE,
            YRR_LOCATIONS_TABLE
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove options
        delete_option('yrr_db_version');
        delete_option('yrr_version');
    }
    
    /**
     * Get table status information
     */
    public static function get_table_status() {
        global $wpdb;
        
        $tables = array(
            'reservations' => YRR_RESERVATIONS_TABLE,
            'tables' => YRR_TABLES_TABLE,
            'hours' => YRR_HOURS_TABLE,
            'settings' => YRR_SETTINGS_TABLE,
            'coupons' => YRR_COUPONS_TABLE,
            'locations' => YRR_LOCATIONS_TABLE,
            'points' => YRR_POINTS_TABLE
        );
        
        $status = array();
        
        foreach ($tables as $name => $table) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table
            ));
            
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                $status[$name] = array(
                    'exists' => true,
                    'count' => intval($count)
                );
            } else {
                $status[$name] = array(
                    'exists' => false,
                    'count' => 0
                );
            }
        }
        
        return $status;
    }
    
    /**
     * Repair corrupted tables
     */
    public static function repair_tables() {
        global $wpdb;
        
        $tables = array(
            YRR_RESERVATIONS_TABLE,
            YRR_TABLES_TABLE,
            YRR_HOURS_TABLE,
            YRR_SETTINGS_TABLE,
            YRR_COUPONS_TABLE,
            YRR_LOCATIONS_TABLE,
            YRR_POINTS_TABLE
        );
        
        $results = array();
        
        foreach ($tables as $table) {
            $result = $wpdb->query("REPAIR TABLE $table");
            $results[$table] = $result !== false;
        }
        
        return $results;
    }
    
    /**
     * Optimize database tables
     */
    public static function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            YRR_RESERVATIONS_TABLE,
            YRR_TABLES_TABLE,
            YRR_HOURS_TABLE,
            YRR_SETTINGS_TABLE,
            YRR_COUPONS_TABLE,
            YRR_LOCATIONS_TABLE,
            YRR_POINTS_TABLE
        );
        
        $results = array();
        
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE $table");
            $results[$table] = $result !== false;
        }
        
        return $results;
    }
    
    /**
     * Get database size information
     */
    public static function get_database_size() {
        global $wpdb;
        
        $tables = array(
            YRR_RESERVATIONS_TABLE,
            YRR_TABLES_TABLE,
            YRR_HOURS_TABLE,
            YRR_SETTINGS_TABLE,
            YRR_COUPONS_TABLE,
            YRR_LOCATIONS_TABLE,
            YRR_POINTS_TABLE
        );
        
        $total_size = 0;
        $table_sizes = array();
        
        foreach ($tables as $table) {
            $size = $wpdb->get_var($wpdb->prepare(
                "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'DB Size in MB' 
                 FROM information_schema.tables 
                 WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            $size = floatval($size);
            $table_sizes[$table] = $size;
            $total_size += $size;
        }
        
        return array(
            'total_size' => $total_size,
            'table_sizes' => $table_sizes
        );
    }
}
