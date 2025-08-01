<?php
/**
 * Settings Page View for Yenolx Restaurant Reservation System
 *
 * This file renders the main settings page with multiple tabs for different
 * configuration areas, using the WordPress Settings API.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Determine the active tab
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

?>

<div class="wrap yrr-settings">
    <h1><?php _e('Plugin Settings', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('Configure all settings for the restaurant reservation system.', 'yrr'); ?></p>

    <!-- Settings Tabs Navigation -->
    <nav class="nav-tab-wrapper">
        <a href="?page=yrr-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General', 'yrr'); ?>
        </a>
        <a href="?page=yrr-settings&tab=booking" class="nav-tab <?php echo $active_tab == 'booking' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Booking Rules', 'yrr'); ?>
        </a>
        <a href="?page=yrr-settings&tab=notifications" class="nav-tab <?php echo $active_tab == 'notifications' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Notifications', 'yrr'); ?>
        </a>
         <a href="?page=yrr-settings&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Advanced', 'yrr'); ?>
        </a>
    </nav>

    <form action="options.php" method="post" class="yrr-settings-form">
        <?php
        // Output security fields for the appropriate group
        settings_fields('yrr_settings_group_' . $active_tab);

        // Output the settings sections for the active tab
        do_settings_sections('yrr-settings-' . $active_tab);
        
        // Add a submit button
        submit_button(__('Save Settings', 'yrr'));
        ?>
    </form>
</div>

<style>
    /* Basic styling for the settings page - can be moved to admin.css */
    .yrr-settings .yrr-page-description {
        font-size: 1.1em;
        color: #666;
    }
    .yrr-settings-form {
        margin-top: 20px;
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
    }
    .yrr-settings-form .form-table th {
        width: 250px;
    }
    .yrr-settings-form .form-table td input[type="text"],
    .yrr-settings-form .form-table td input[type="email"],
    .yrr-settings-form .form-table td input[type="number"],
    .yrr-settings-form .form-table td textarea {
        width: 100%;
        max-width: 400px;
    }
</style>
