<?php
/**
 * Plugin installer and activation handler
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

class YRR_Installer {
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables
        YRR_Database::create_tables();
        
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
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Remove activation flag
        delete_option('yrr_activation_flag');
    }
    
    /**
     * Run installation/upgrade
     */
    public static function install() {
        // Check if database needs update
        if (YRR_Database::needs_update()) {
            YRR_Database::create_tables();
        }
        
        // Update version
        update_option('yrr_version', YRR_VERSION);
    }
}
