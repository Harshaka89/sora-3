<?php
if (!defined('ABSPATH')) exit;

// Load all settings values
$fields = [
    'restaurant_name' => __('Restaurant Name', 'yrr'),
    'restaurant_email' => __('Contact Email', 'yrr'),
    'restaurant_phone' => __('Contact Phone', 'yrr'),
    'restaurant_address' => __('Address', 'yrr'),
    'currency_symbol' => __('Currency Symbol', 'yrr'),
    'max_party_size' => __('Maximum Party Size', 'yrr'),
    'slot_duration' => __('Slot Duration (min)', 'yrr'),
    'advance_booking_days' => __('Advance Booking (days)', 'yrr'),
    'booking_buffer_hours' => __('Buffer Hours Before Close', 'yrr'),
    'auto_confirm' => __('Auto-confirm Reservations', 'yrr'),
];

$settings = [];
foreach ($fields as $key => $label) {
    $settings[$key] = method_exists('YRR_Settings_Model', 'get_setting')
        ? YRR_Settings_Model::get_setting($key)
        : '';
}

// Save if form submitted
if (isset($_POST['yrr_settings_nonce']) && wp_verify_nonce($_POST['yrr_settings_nonce'], 'yrr_save_settings')) {
    foreach ($fields as $key => $label) {
        $value = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
        if (method_exists('YRR_Settings_Model', 'set_setting')) {
            YRR_Settings_Model::set_setting($key, $value);
        }
        $settings[$key] = $value; // update for display
    }
    echo '<div class="notice notice-success"><p>' . esc_html__('Settings updated!', 'yrr') . '</p></div>';
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Reservation System Settings', 'yrr'); ?></h1>
    <form method="post">
        <?php wp_nonce_field('yrr_save_settings', 'yrr_settings_nonce'); ?>
        <table class="form-table">
            <?php foreach ($fields as $key => $label): ?>
                <tr>
                    <th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                    <td>
                        <?php if ($key === 'auto_confirm'): ?>
                            <select id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>">
                                <option value="0" <?php selected($settings[$key], '0'); ?>><?php esc_html_e('Require Manual Approval', 'yrr'); ?></option>
                                <option value="1" <?php selected($settings[$key], '1'); ?>><?php esc_html_e('Auto-confirm', 'yrr'); ?></option>
                            </select>
                        <?php elseif (in_array($key, ['max_party_size', 'slot_duration', 'advance_booking_days', 'booking_buffer_hours'])): ?>
                            <input type="number" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($settings[$key]); ?>" min="1" required style="width:80px;" />
                        <?php else: ?>
                            <input type="text" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($settings[$key]); ?>" class="regular-text" />
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'yrr'); ?></button>
        </p>
    </form>
</div>
