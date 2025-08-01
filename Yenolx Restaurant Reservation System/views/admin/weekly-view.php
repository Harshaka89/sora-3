<?php
/**
 * Weekly Calendar View for Yenolx Restaurant Reservation System
 *
 * This file renders the structural container and controls for the main
 * interactive calendar. The actual calendar is rendered via JavaScript.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

?>

<div class="wrap yrr-weekly-view">
    <h1><?php _e('Weekly Calendar View', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('A visual overview of your reservations for the week. Click on a booking to see details.', 'yrr'); ?></p>

    <!-- Calendar Header Controls -->
    <div class="yrr-calendar-header">
        <div class="yrr-calendar-nav">
            <button id="yrr-calendar-prev" class="button"><?php _e('&laquo; Previous Week', 'yrr'); ?></button>
            <button id="yrr-calendar-today" class="button"><?php _e('Today', 'yrr'); ?></button>
            <button id="yrr-calendar-next" class="button"><?php _e('Next Week &raquo;', 'yrr'); ?></button>
        </div>
        <h2 class="yrr-calendar-title">
            <!-- The calendar title (e.g., "August 2025") will be dynamically inserted here by JavaScript -->
        </h2>
        <div class="yrr-calendar-actions">
             <a href="#" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span> <?php _e('Add Reservation', 'yrr'); ?>
            </a>
        </div>
    </div>

    <!-- Main Calendar Container -->
    <div id="yrr-calendar-wrapper" class="yrr-panel">
        <div class="yrr-placeholder-content">
            <p>
                <strong><?php _e('Calendar Loading...', 'yrr'); ?></strong>
            </p>
            <p>
                <?php _e('The interactive calendar will be displayed here. This requires a JavaScript library like FullCalendar.js to be integrated.', 'yrr'); ?>
            </p>
        </div>
    </div>
</div>

<style>
    /* Basic styling for the calendar view - can be moved to admin.css */
    .yrr-weekly-view .yrr-page-description {
        font-size: 1.1em;
        color: #666;
    }
    .yrr-calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 20px 0;
        flex-wrap: wrap;
    }
    .yrr-calendar-title {
        font-size: 1.5em;
        margin: 0;
    }
    .yrr-calendar-nav, .yrr-calendar-actions {
        display: flex;
        gap: 10px;
    }
    #yrr-calendar-wrapper {
        background: #fff;
        border: 1px solid #ccd0d4;
        padding: 20px;
        min-height: 500px;
    }
    .yrr-placeholder-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100%;
        border: 2px dashed #e0e0e0;
        padding: 40px;
        text-align: center;
        color: #999;
    }
</style>
