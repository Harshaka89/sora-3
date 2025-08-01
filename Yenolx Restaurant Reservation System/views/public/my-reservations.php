<?php
/**
 * My Reservations View for Yenolx Restaurant Reservation System
 *
 * This file renders the customer-facing portal to look up reservations.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
?>

<div id="yrr-my-reservations-wrapper" class="yrr-form">
    <h2><?php _e('My Reservations', 'yrr'); ?></h2>
    <p><?php _e('Enter the email address you used to book to find your reservations.', 'yrr'); ?></p>

    <form id="yrr-lookup-form">
        <div class="yrr-form-group">
            <label for="yrr-lookup-email"><?php _e('Your Email Address', 'yrr'); ?></label>
            <input type="email" id="yrr-lookup-email" name="email" required>
        </div>
        <div class="yrr-form-group">
            <button type="submit" class="yrr-button"><?php _e('Find My Reservations', 'yrr'); ?></button>
        </div>
    </form>

    <div id="yrr-my-reservations-results" style="margin-top: 2em;">
        <!-- Reservation results will be loaded here by JavaScript -->
    </div>
</div>
