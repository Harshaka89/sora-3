<?php
/**
 * Settings View for Yenolx Restaurant Reservation System
 *
 * This file renders the main settings page with multiple tabs.
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

<div class="wrap yrr-settings-page">
    <h1><?php _e('Restaurant Settings', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('Configure the core settings for your reservation system.', 'yrr'); ?></p>

    <!-- Settings Tabs Navigation -->
    <h2 class="nav-tab-wrapper">
        <a href="?page=yrr-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General', 'yrr'); ?></a>
        <a href="?page=yrr-settings&tab=booking" class="nav-tab <?php echo $active_tab == 'booking' ? 'nav-tab-active' : ''; ?>"><?php _e('Booking Rules', 'yrr'); ?></a>
    </h2>

    <form method="post" action="options.php">
        <?php
        // Output nonce, action, and option_page fields for the active tab
        if ($active_tab == 'general') {
            settings_fields('yrr_settings_group_general');
            do_settings_sections('yrr-settings-general');
        } elseif ($active_tab == 'booking') {
            settings_fields('yrr_settings_group_booking');
            do_settings_sections('yrr-settings-booking');
        }
        
        submit_button();
        ?>
    </form>
</div>
