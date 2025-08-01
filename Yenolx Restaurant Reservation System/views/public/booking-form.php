<?php
/**
 * Public Booking Form View for Yenolx Restaurant Reservation System
 *
 * This file renders the customer-facing reservation form.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Fetch booking rules to apply to the form
$booking_rules = YRR_Settings_Model::get_booking_rules();
$max_party_size = $booking_rules['max_party_size'] ?? 12;

?>

<div id="yrr-booking-form-wrapper">
    <form id="yrr-booking-form" class="yrr-form">
        
        <div id="yrr-form-messages"></div>

        <fieldset>
            <legend><?php _e('Find a Table', 'yrr'); ?></legend>

            <div class="yrr-form-row">
                <!-- Date Selection -->
                <div class="yrr-form-group">
                    <label for="yrr-date"><?php _e('Date', 'yrr'); ?></label>
                    <input type="date" id="yrr-date" name="reservation_date" required>
                </div>

                <!-- Party Size Selection -->
                <div class="yrr-form-group">
                    <label for="yrr-party-size"><?php _e('Party Size', 'yrr'); ?></label>
                    <select id="yrr-party-size" name="party_size" required>
                        <?php for ($i = 1; $i <= $max_party_size; $i++) : ?>
                            <option value="<?php echo $i; ?>"><?php echo sprintf(_n('%d Person', '%d People', $i, 'yrr'), $i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- Available Time Slots -->
            <div class="yrr-form-group" id="yrr-time-slot-group" style="display: none;">
                <label><?php _e('Available Times', 'yrr'); ?></label>
                <div id="yrr-time-slots" class="yrr-time-slots-container">
                    <!-- Time slots will be populated here by JavaScript -->
                    <p class="yrr-loading-slots"><?php _e('Select a date and party size to see available times.', 'yrr'); ?></p>
                </div>
                <input type="hidden" id="yrr-selected-time" name="reservation_time" required>
            </div>
        </fieldset>

        <!-- Customer Details Section (initially hidden) -->
        <fieldset id="yrr-customer-details" style="display: none;">
            <legend><?php _e('Your Details', 'yrr'); ?></legend>

            <div class="yrr-form-row">
                <div class="yrr-form-group">
                    <label for="yrr-customer-name"><?php _e('Full Name', 'yrr'); ?></label>
                    <input type="text" id="yrr-customer-name" name="customer_name" required>
                </div>
                <div class="yrr-form-group">
                    <label for="yrr-customer-email"><?php _e('Email Address', 'yrr'); ?></label>
                    <input type="email" id="yrr-customer-email" name="customer_email" required>
                </div>
            </div>

             <div class="yrr-form-group">
                <label for="yrr-special-requests"><?php _e('Special Requests (Optional)', 'yrr'); ?></label>
                <textarea id="yrr-special-requests" name="special_requests" rows="3"></textarea>
            </div>
        </fieldset>
        
        <!-- Submit Button -->
        <div class="yrr-form-group">
            <button type="submit" id="yrr-submit-booking" class="yrr-button" disabled><?php _e('Complete Reservation', 'yrr'); ?></button>
        </div>

    </form>
</div>

<style>
    /* Basic styling for public form - can be moved to public.css */
    #yrr-booking-form-wrapper { max-width: 600px; margin: 2em auto; font-family: sans-serif; }
    .yrr-form fieldset { border: 1px solid #ddd; padding: 1.5em; margin-bottom: 1.5em; }
    .yrr-form legend { font-weight: bold; font-size: 1.2em; padding: 0 0.5em; }
    .yrr-form-row { display: flex; gap: 1em; }
    .yrr-form-group { flex: 1; margin-bottom: 1em; display: flex; flex-direction: column; }
    .yrr-form-group label { margin-bottom: 0.5em; font-weight: 600; }
    .yrr-form-group input, .yrr-form-group select, .yrr-form-group textarea { padding: 0.8em; border: 1px solid #ccc; border-radius: 4px; font-size: 1em; }
    .yrr-time-slots-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 0.5em; }
    .yrr-time-slot { padding: 0.8em; border: 1px solid #0073aa; color: #0073aa; text-align: center; border-radius: 4px; cursor: pointer; }
    .yrr-time-slot:hover { background-color: #f0f6fa; }
    .yrr-time-slot.selected { background-color: #0073aa; color: #fff; font-weight: bold; }
    .yrr-button { width: 100%; padding: 1em; background-color: #0073aa; color: #fff; border: none; border-radius: 4px; font-size: 1.1em; cursor: pointer; }
    .yrr-button:disabled { background-color: #a0a5aa; cursor: not-allowed; }
    #yrr-form-messages { padding: 1em; margin-bottom: 1em; border-radius: 4px; display: none; }
    #yrr-form-messages.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    #yrr-form-messages.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>
