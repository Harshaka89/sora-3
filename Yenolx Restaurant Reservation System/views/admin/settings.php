<?php
if (!defined('ABSPATH')) exit;

// 1. Define setting fields
$fields = [
    'restaurant_name'        => __('Restaurant Name',   'yrr'),
    'restaurant_email'       => __('Contact Email',      'yrr'),
    'restaurant_phone'       => __('Contact Phone',      'yrr'),
    'restaurant_address'     => __('Address',            'yrr'),
    'currency_symbol'        => __('Currency Symbol',    'yrr'),
    'max_party_size'         => __('Maximum Party Size', 'yrr'),
    'slot_duration'          => __('Slot Duration (min)', 'yrr'),
    'advance_booking_days'   => __('Advance Booking Days', 'yrr'),
    'booking_buffer_hours'   => __('Buffer Before Close (hours)', 'yrr'),
    'auto_confirm'           => __('Auto-confirm Reservations', 'yrr'),
];

// 2. Load current settings
$settings = [];
foreach ($fields as $key => $label) {
    $settings[$key] = method_exists('YRR_Settings_Model', 'get_setting')
        ? YRR_Settings_Model::get_setting($key)
        : '';
}

// 3. Save if POSTed
if (isset($_POST['yrr_settings_nonce']) && wp_verify_nonce($_POST['yrr_settings_nonce'], 'yrr_save_settings')) {
    foreach ($fields as $key => $label) {
        $value = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
        if (method_exists('YRR_Settings_Model', 'set_setting')) {
            YRR_Settings_Model::set_setting($key, $value);
            $settings[$key] = $value; // update for display
        }
    }
    echo '<div class="notice notice-success"><p>' . esc_html__('Settings Updated!', 'yrr') . '</p></div>';
}
?>
<div class="wrap">
    <h1 style="font-size:2.1rem; color:#2c3e50; margin-bottom:20px;"><?php esc_html_e('Reservation System Settings', 'yrr'); ?></h1>
    <form method="post" style="background:white; padding:30px; max-width:700px; border-radius:12px; box-shadow:0 4px 24px rgba(0,0,0,0.07);">
        <?php wp_nonce_field('yrr_save_settings', 'yrr_settings_nonce'); ?>
        <table class="form-table" style="width:100%;">
            <?php foreach ($fields as $key => $label): ?>
                <tr>
                    <th style="text-align:right;vertical-align:middle;width:42%;">
                        <label for="<?php echo esc_attr($key); ?>" style="font-weight:bold;"><?php echo esc_html($label); ?></label>
                    </th>
                    <td style="padding:10px 0;">
                        <?php if ($key === 'auto_confirm'): ?>
                            <label style="margin-right:16px;">
                                <input type="radio" name="<?php echo esc_attr($key); ?>" value="0" <?php checked($settings[$key], '0'); ?> /> 
                                <span style="margin-left:5px;"><?php esc_html_e('Manual Approval', 'yrr'); ?></span>
                            </label>
                            <label>
                                <input type="radio" name="<?php echo esc_attr($key); ?>" value="1" <?php checked($settings[$key], '1'); ?> /> 
                                <span style="margin-left:5px;"><?php esc_html_e('Auto-confirm', 'yrr'); ?></span>
                            </label>
                        <?php elseif (in_array($key, ['max_party_size', 'slot_duration', 'advance_booking_days', 'booking_buffer_hours'])): ?>
                            <input type="number" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($settings[$key]); ?>" min="1" required style="width:80px;padding:6px;border-radius:6px;border:1.5px solid #e9ecef;" />
                        <?php else: ?>
                            <input type="text" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($settings[$key]); ?>" class="regular-text" style="width:100%;padding:7px;border-radius:6px;border:1.5px solid #e9ecef;"/>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p style="margin-top:22px;">
            <button type="submit" class="button button-primary button-large" style="padding:13px 40px;font-size:1.2rem;"><?php esc_html_e('Save Settings', 'yrr'); ?></button>
        </p>
    </form>
</div>
<tr>
    <th><?php esc_html_e('Bookings Status', 'yrr'); ?></th>
    <td>
        <label style="display:inline-flex;align-items:center;gap:10px;">
          <input type="checkbox" name="bookings_open" value="1" <?php checked($settings['bookings_open'], '1'); ?> />
          <span style="font-size:1.1em;color:#007cba;font-weight:600;">
            <?php echo ($settings['bookings_open']) ? 'Open (Bookings Enabled)' : 'Closed (Show Amusement)'; ?>
          </span>
        </label>
    </td>
</tr>
