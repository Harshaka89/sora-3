<?php
/**
 * Admin Dashboard View for Yenolx Restaurant Reservation System
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// The $stats variable is now passed directly to this view.
?>

<div class="wrap yrr-dashboard">
    <h1><?php _e('Dashboard', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('Welcome to the Yenolx Restaurant Reservation System. Here is a summary of your activity.', 'yrr'); ?></p>

    <!-- Statistics Cards -->
    <div id="yrr-stats-cards">
        <div class="yrr-stat-card">
            <h3><?php _e('Reservations Today', 'yrr'); ?></h3>
            <p class="stat-number"><?php echo esc_html($stats['today'] ?? 0); ?></p>
        </div>
        <div class="yrr-stat-card">
            <h3><?php _e('Guests Today', 'yrr'); ?></h3>
            <p class="stat-number"><?php echo esc_html($stats['today_guests'] ?? 0); ?></p>
        </div>
        <div class="yrr-stat-card">
            <h3><?php _e('Pending Approval', 'yrr'); ?></h3>
            <p class="stat-number"><?php echo esc_html($stats['pending'] ?? 0); ?></p>
        </div>
        <div class="yrr-stat-card">
            <h3><?php _e('Total Confirmed', 'yrr'); ?></h3>
            <p class="stat-number"><?php echo esc_html($stats['confirmed'] ?? 0); ?></p>
        </div>
    </div>
    
</div>
