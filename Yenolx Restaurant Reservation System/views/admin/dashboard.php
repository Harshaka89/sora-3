<?php
/**
 * Admin Dashboard View for Yenolx Restaurant Reservation System
 *
 * This file renders the main dashboard page, displaying key statistics,
 * quick action buttons, and an overview of recent activity.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure the necessary model is available
if (!class_exists('YRR_Reservation_Model')) {
    echo '<div class="notice notice-error"><p>Error: The Reservation Model is missing and the dashboard cannot be displayed.</p></div>';
    return;
}

// Fetch dashboard statistics using our model
$stats = YRR_Reservation_Model::get_dashboard_stats();

// Prepare variables for display
$reservations_today = $stats['today'] ?? 0;
$pending_reservations = $stats['pending'] ?? 0;
$confirmed_reservations = $stats['confirmed'] ?? 0;
$total_guests_today = $stats['today_guests'] ?? 0;

?>

<div class="wrap yrr-dashboard">
    <h1><?php _e('Dashboard', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('A quick overview of your restaurant\'s reservation activity.', 'yrr'); ?></p>

    <!-- Main Statistics Cards -->
    <div class="yrr-kpi-cards">
        <div class="yrr-card">
            <div class="yrr-card-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
            <div class="yrr-card-content">
                <div class="yrr-card-title"><?php _e('Reservations Today', 'yrr'); ?></div>
                <div class="yrr-card-value"><?php echo esc_html($reservations_today); ?></div>
            </div>
        </div>
        <div class="yrr-card">
            <div class="yrr-card-icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="yrr-card-content">
                <div class="yrr-card-title"><?php _e('Guests (Covers) Today', 'yrr'); ?></div>
                <div class="yrr-card-value"><?php echo esc_html($total_guests_today); ?></div>
            </div>
        </div>
        <div class="yrr-card">
            <div class="yrr-card-icon"><span class="dashicons dashicons-warning"></span></div>
            <div class="yrr-card-content">
                <div class="yrr-card-title"><?php _e('Pending Approval', 'yrr'); ?></div>
                <div class="yrr-card-value"><?php echo esc_html($pending_reservations); ?></div>
            </div>
        </div>
        <div class="yrr-card">
            <div class="yrr-card-icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="yrr-card-content">
                <div class="yrr-card-title"><?php _e('Confirmed Today', 'yrr'); ?></div>
                <div class="yrr-card-value"><?php echo esc_html($confirmed_reservations); ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Action Buttons -->
    <div class="yrr-quick-actions">
        <a href="<?php echo admin_url('admin.php?page=yrr-reservations&status=pending'); ?>" class="button button-primary">
            <span class="dashicons dashicons-warning"></span> <?php _e('Review Pending Bookings', 'yrr'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=yrr-calendar'); ?>" class="button">
            <span class="dashicons dashicons-calendar"></span> <?php _e('Go to Calendar View', 'yrr'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=yrr-tables'); ?>" class="button">
            <span class="dashicons dashicons-tag"></span> <?php _e('Manage Tables', 'yrr'); ?>
        </a>
         <a href="<?php echo admin_url('admin.php?page=yrr-settings'); ?>" class="button">
            <span class="dashicons dashicons-admin-settings"></span> <?php _e('View All Settings', 'yrr'); ?>
        </a>
    </div>

    <!-- Future Content Area -->
    <div class="yrr-dashboard-main-content">
        <div class="yrr-panel">
            <h2><?php _e('Today\'s Activity', 'yrr'); ?></h2>
            <p><?php _e('A timeline of upcoming and recent reservations will be displayed here.', 'yrr'); ?></p>
            <!-- Placeholder for a list of today's reservations -->
            <div class="yrr-placeholder-content">
                <p><em>[Today's Reservations List - Coming Soon]</em></p>
            </div>
        </div>
        <div class="yrr-panel">
            <h2><?php _e('Booking Trends', 'yrr'); ?></h2>
            <p><?php _e('A chart showing booking trends for the last 7 days.', 'yrr'); ?></p>
            <!-- Placeholder for the chart canvas -->
            <div class="yrr-placeholder-content">
                 <p><em>[Bookings Chart - Coming Soon]</em></p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Basic styling for the dashboard - can be moved to admin.css */
    .yrr-dashboard .yrr-page-description {
        font-size: 1.1em;
        color: #666;
    }
    .yrr-kpi-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .yrr-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        display: flex;
        align-items: center;
    }
    .yrr-card-icon {
        font-size: 36px;
        color: #50575e;
        margin-right: 20px;
    }
    .yrr-card-title {
        font-weight: 600;
        color: #3c434a;
    }
    .yrr-card-value {
        font-size: 2.2em;
        font-weight: 700;
        color: #1d2327;
        line-height: 1.2;
    }
    .yrr-quick-actions {
        margin-top: 25px;
        display: flex;
        gap: 10px;
    }
    .yrr-dashboard-main-content {
        margin-top: 25px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .yrr-panel {
        background: #fff;
        border: 1px solid #ccd0d4;
        padding: 20px;
    }
    .yrr-placeholder-content {
        border: 2px dashed #e0e0e0;
        padding: 40px;
        text-align: center;
        color: #999;
        margin-top: 15px;
    }
</style>
