<?php
/**
 * Operating Hours View for Yenolx Restaurant Reservation System
 *
 * This file renders the form for setting weekly business hours.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure the necessary model is available
if (!class_exists('YRR_Hours_Model')) {
    echo '<div class="notice notice-error"><p>Error: The Hours Model is missing and operating hours cannot be displayed.</p></div>';
    return;
}

// Fetch the current operating hours
$hours_data = YRR_Hours_Model::get_all();
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

?>

<div class="wrap yrr-hours">
    <h1><?php _e('Operating Hours', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('Set your standard weekly business hours. This schedule determines when customers can book.', 'yrr'); ?></p>

    <form method="post" action="">
        <?php wp_nonce_field('yrr_save_hours_nonce', 'yrr_hours_nonce'); ?>
        <input type="hidden" name="action" value="yrr_save_hours">

        <div class="yrr-panel">
            <table class="form-table yrr-hours-table">
                <thead>
                    <tr>
                        <th><?php _e('Day of the Week', 'yrr'); ?></th>
                        <th><?php _e('Status', 'yrr'); ?></th>
                        <th><?php _e('Open Time', 'yrr'); ?></th>
                        <th><?php _e('Close Time', 'yrr'); ?></th>
                        <th><?php _e('Break Start', 'yrr'); ?></th>
                        <th><?php _e('Break End', 'yrr'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days_of_week as $day) : 
                        $hour_entry = null;
                        foreach ($hours_data as $hd) {
                            if ($hd->day_of_week === $day) {
                                $hour_entry = $hd;
                                break;
                            }
                        }
                    ?>
                        <tr>
                            <th><?php echo esc_html($day); ?></th>
                            <td>
                                <input type="hidden" name="hours[<?php echo esc_attr($day); ?>][id]" value="<?php echo esc_attr($hour_entry->id ?? 0); ?>">
                                <select name="hours[<?php echo esc_attr($day); ?>][is_closed]" class="day-status">
                                    <option value="0" <?php selected($hour_entry->is_closed ?? 0, 0); ?>><?php _e('Open', 'yrr'); ?></option>
                                    <option value="1" <?php selected($hour_entry->is_closed ?? 0, 1); ?>><?php _e('Closed', 'yrr'); ?></option>
                                </select>
                            </td>
                            <td><input type="time" name="hours[<?php echo esc_attr($day); ?>][open_time]" value="<?php echo esc_attr($hour_entry->open_time ?? '09:00'); ?>" class="time-input"></td>
                            <td><input type="time" name="hours[<?php echo esc_attr($day); ?>][close_time]" value="<?php echo esc_attr($hour_entry->close_time ?? '22:00'); ?>" class="time-input"></td>
                            <td><input type="time" name="hours[<?php echo esc_attr($day); ?>][break_start]" value="<?php echo esc_attr($hour_entry->break_start ?? ''); ?>" class="time-input"></td>
                            <td><input type="time" name="hours[<?php echo esc_attr($day); ?>][break_end]" value="<?php echo esc_attr($hour_entry->break_end ?? ''); ?>" class="time-input"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php submit_button(__('Save Schedule', 'yrr')); ?>
    </form>
</div>

<style>
    /* Basic styling for the hours page - can be moved to admin.css */
    .yrr-hours .yrr-page-description {
        font-size: 1.1em;
        color: #666;
    }
    .yrr-panel {
        background: #fff;
        border: 1px solid #ccd0d4;
        padding: 0 20px 20px;
        margin-top: 20px;
    }
    .yrr-hours-table {
        width: 100%;
        margin: 0;
    }
    .yrr-hours-table th, .yrr-hours-table td {
        text-align: left;
        padding: 15px 10px;
        vertical-align: middle;
    }
    .yrr-hours-table thead th {
        border-bottom: 1px solid #ddd;
    }
    .yrr-hours-table tbody tr:not(:last-child) td,
    .yrr-hours-table tbody tr:not(:last-child) th {
        border-bottom: 1px solid #f0f0f1;
    }
    .yrr-hours-table .time-input, .yrr-hours-table select {
        width: 100%;
        max-width: 150px;
    }
</style>

<script>
    // Simple script to toggle time inputs based on "Open/Closed" status
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.yrr-hours-table tbody tr');
        
        function toggleRow(row) {
            const statusSelect = row.querySelector('.day-status');
            const timeInputs = row.querySelectorAll('.time-input');
            const isClosed = statusSelect.value === '1';

            timeInputs.forEach(input => {
                input.disabled = isClosed;
                if (isClosed) {
                    input.style.backgroundColor = '#f0f0f1';
                } else {
                    input.style.backgroundColor = '';
                }
            });
        }

        rows.forEach(row => {
            const statusSelect = row.querySelector('.day-status');
            statusSelect.addEventListener('change', () => toggleRow(row));
            toggleRow(row); // Initial check on page load
        });
    });
</script>
