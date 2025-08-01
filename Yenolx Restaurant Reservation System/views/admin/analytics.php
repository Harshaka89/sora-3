<?php
/**
 * Analytics Page View for Yenolx Restaurant Reservation System
 *
 * This file renders the analytics dashboard with charts and data visualizations.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

?>

<div class="wrap yrr-analytics">
    <h1><?php _e('Analytics & Reports', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('Visualize your reservation data to gain insights into your business performance.', 'yrr'); ?></p>

    <!-- Filter Controls -->
    <div class="yrr-panel yrr-filters">
        <form method="get">
            <input type="hidden" name="page" value="yrr-analytics" />
            <div class="yrr-filter-group">
                <div class="yrr-filter-item">
                    <label for="date-range"><?php _e('Date Range:', 'yrr'); ?></label>
                    <select id="date-range" name="range">
                        <option value="last_7_days"><?php _e('Last 7 Days', 'yrr'); ?></option>
                        <option value="last_30_days" selected><?php _e('Last 30 Days', 'yrr'); ?></option>
                        <option value="this_month"><?php _e('This Month', 'yrr'); ?></option>
                        <option value="last_month"><?php _e('Last Month', 'yrr'); ?></option>
                    </select>
                </div>
                <div class="yrr-filter-item">
                    <label for="location-filter"><?php _e('Location:', 'yrr'); ?></label>
                    <select id="location-filter" name="location_id">
                        <option value="all"><?php _e('All Locations', 'yrr'); ?></option>
                        <!-- Location options will be populated dynamically -->
                    </select>
                </div>
                <div class="yrr-filter-item">
                    <input type="submit" class="button button-primary" value="<?php _e('Apply Filters', 'yrr'); ?>">
                </div>
            </div>
        </form>
    </div>

    <!-- Chart and KPI Grid -->
    <div class="yrr-analytics-grid">
        <div class="yrr-panel chart-panel">
            <h3><?php _e('Bookings Over Time', 'yrr'); ?></h3>
            <div class="yrr-placeholder-content">
                <p><em>[Line Chart: Total Reservations and Guests]</em></p>
                <canvas id="bookingsChart"></canvas>
            </div>
        </div>
        <div class="yrr-panel chart-panel">
            <h3><?php _e('Peak Reservation Times', 'yrr'); ?></h3>
            <div class="yrr-placeholder-content">
                <p><em>[Bar Chart: Bookings by Hour]</em></p>
                <canvas id="peakTimesChart"></canvas>
            </div>
        </div>
        <div class="yrr-panel chart-panel">
            <h3><?php _e('Table Utilization', 'yrr'); ?></h3>
            <div class="yrr-placeholder-content">
                 <p><em>[Pie Chart or Table: Bookings per Table]</em></p>
                 <canvas id="tableUtilizationChart"></canvas>
            </div>
        </div>
        <div class="yrr-panel chart-panel">
            <h3><?php _e('Coupon Performance', 'yrr'); ?></h3>
            <div class="yrr-placeholder-content">
                <p><em>[Table: Usage counts for top coupons]</em></p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Basic styling for the analytics page - can be moved to admin.css */
    .yrr-analytics .yrr-page-description {
        font-size: 1.1em;
        color: #666;
    }
    .yrr-panel {
        background: #fff;
        border: 1px solid #ccd0d4;
        padding: 20px;
        margin-top: 20px;
    }
    .yrr-filters .yrr-filter-group {
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .yrr-analytics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .yrr-placeholder-content {
        border: 2px dashed #e0e0e0;
        padding: 40px;
        text-align: center;
        color: #999;
        margin-top: 15px;
        min-height: 300px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .yrr-placeholder-content canvas {
        display: none; /* Hide canvas until it's populated with data by JS */
    }
</style>
