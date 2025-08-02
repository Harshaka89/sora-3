<?php
<?php include_once('../../includes/auth-check.php'); ?>

if (!defined('ABSPATH')) exit;

// 1. Define settings fields (add more as needed)
$fields = [
    'restaurant_open'        => __('Restaurant Open', 'yrr'),
    'restaurant_name'        => __('Restaurant Name',   'yrr'),
    'restaurant_email'       => __('Contact Email',      'yrr'),
    'restaurant_phone'       => __('Contact Phone',      'yrr'),
    'restaurant_address'     => __('Address',            'yrr'),
    'currency_symbol'        => __('Currency Symbol',    'yrr'),
    'base_price_per_person'  => __('Base Price Per Person', 'yrr'),
];

// 2. Load current settings
$settings = [];
foreach ($fields as $key => $label) {
    $settings[$key] = method_exists('YRR_Settings_Model', 'get_setting')
        ? YRR_Settings_Model::get_setting($key)
        : '';
}

// 3. Handle form save
$count = 0; $error_count = 0;
if (!empty($_POST['settings_nonce']) && wp_verify_nonce($_POST['settings_nonce'], 'yrr_settings_save')) {
    foreach ($fields as $key => $label) {
        if ($key === 'restaurant_open') {
            $value = isset($_POST[$key]) ? ($_POST[$key] == '1' ? '1' : '0') : '1';
        } elseif ($key === 'base_price_per_person') {
            $value = isset($_POST[$key]) ? floatval($_POST[$key]) : 0;
        } else {
            $value = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
        }
        if (method_exists('YRR_Settings_Model', 'set_setting')) {
            YRR_Settings_Model::set_setting($key, $value);
            $settings[$key] = $value;
            $count++;
        } else {
            $error_count++;
        }
    }
    // Redirect for clean UI feedback
    $url = add_query_arg([
        'page' => 'yrr-settings',
        'message' => 'saved',
        'count' => $count,
        'error_count' => $error_count
    ], admin_url('admin.php'));
    wp_redirect($url); exit;
}
?>

<div class="wrap">
    <div style="max-width: 1200px; margin: 20px auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
        <!-- Header -->
        <div style="text-align:center;margin-bottom:40px;padding-bottom:25px;border-bottom:4px solid #667eea;">
            <h1 style="font-size:2.5rem;color:#2c3e50;margin:0;">⚙️ Yenolx Restaurant Settings v1.5</h1>
            <p style="color:#6c757d;margin:15px 0 0 0;font-size:1.1rem;">Complete restaurant management configuration</p>
        </div>

        <!-- Success Message -->
        <?php if (isset($_GET['message']) && $_GET['message'] == 'saved'): ?>
            <div style="background:#d4edda;color:#155724;padding:15px;margin:20px 0;border-radius:8px;border:2px solid #28a745;">
                <h3 style="margin:0 0 10px 0;">✅ Settings Saved Successfully!</h3>
                <p style="margin:0;">
                    <?php echo intval($_GET['count']); ?> settings saved.
                    <?php if (!empty($_GET['error_count']) && $_GET['error_count'] > 0): ?>
                        <br><span style="color:#856404;">⚠️ <?php echo intval($_GET['error_count']); ?> errors occurred.</span>
                    <?php endif; ?>
                    <br><strong>Updated:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Current Status Card -->
        <div style="background:#e3f2fd;padding:20px;border-radius:10px;margin-bottom:30px;border-left:5px solid #2196f3;">
            <h3 style="margin:0 0 15px 0;color:#1976d2;">📊 Current Settings Status</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
                <div>
                    <strong>Status:</strong>
                    <?php echo ($settings['restaurant_open'] ?? '1') == '1' ? '🟢 OPEN' : '🔴 CLOSED'; ?>
                </div>
                <div><strong>Restaurant:</strong> <?php echo esc_html($settings['restaurant_name'] ?? get_bloginfo('name')); ?></div>
                <div><strong>Email:</strong> <?php echo esc_html($settings['restaurant_email'] ?? get_option('admin_email')); ?></div>
                <div><strong>Phone:</strong> <?php echo !empty($settings['restaurant_phone']) ? esc_html($settings['restaurant_phone']) : '📞 Not set'; ?></div>
                <div><strong>Address:</strong> <?php echo !empty($settings['restaurant_address']) ? esc_html($settings['restaurant_address']) : '📍 Not set'; ?></div>
                <div><strong>Base Price:</strong> <?php echo esc_html($settings['currency_symbol'] ?? '$') . number_format(floatval($settings['base_price_per_person'] ?? 0),2); ?>/person</div>
            </div>
        </div>

        <!-- Form -->
        <form method="post" action="">
            <?php wp_nonce_field('yrr_settings_save', 'settings_nonce'); ?>
            <!-- Open/Closed Switch -->
            <div style="margin-bottom:40px;padding:30px;background:#f8f9fa;border-radius:15px;border:3px solid #e9ecef;">
                <h2 style="color:#007cba;font-size:1.6rem;margin:0 0 25px 0;border-bottom:3px solid #007cba;padding-bottom:15px;">
                    🔄 Restaurant Status
                </h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <label style="display:flex;align-items:center;gap:15px;font-size:1.2rem;font-weight:bold;padding:20px;border-radius:10px;cursor:pointer;color:#28a745;background:white;border:3px solid #28a745;">
                        <input type="radio" name="restaurant_open" value="1" <?php checked(($settings['restaurant_open'] ?? '1'), '1'); ?> style="transform:scale(1.5);" />
                        <span>🟢 OPEN - Accept Reservations</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:15px;font-size:1.2rem;font-weight:bold;padding:20px;border-radius:10px;cursor:pointer;color:#dc3545;background:white;border:3px solid #dc3545;">
                        <input type="radio" name="restaurant_open" value="0" <?php checked(($settings['restaurant_open'] ?? '1'), '0'); ?> style="transform:scale(1.5);" />
                        <span>🔴 CLOSED - Stop Reservations (Show Amusement or Closed Notice)</span>
                    </label>
                </div>
                <small style="color:#999;display:block;margin-top:10px;">
                    If CLOSED, public/user booking and time slots will not be offered. (Perfect for holidays or amusement/maintenance days!)
                </small>
            </div>

            <!-- Restaurant Info -->
            <div style="margin-bottom:40px;padding:30px;background:#f8f9fa;border-radius:15px;border:3px solid #e9ecef;">
                <h2 style="color:#007cba;font-size:1.6rem;margin:0 0 25px 0;border-bottom:3px solid #007cba;padding-bottom:15px;">
                    🏪 Restaurant Information
                </h2>
                <!-- Restaurant Name -->
                <div style="margin-bottom:20px;">
                    <label style="display:block;margin-bottom:10px;font-weight:bold;font-size:1.1rem;color:#2c3e50;">🏷️ Restaurant Name *</label>
                    <input type="text" name="restaurant_name" value="<?php echo esc_attr($settings['restaurant_name'] ?? get_bloginfo('name')); ?>"
                        style="width:100%;padding:15px;border:3px solid #e9ecef;border-radius:10px;font-size:1.1rem;box-sizing:border-box;" required>
                </div>
                <!-- Contact Grid -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;margin-bottom:20px;">
                    <div>
                        <label style="display:block;margin-bottom:10px;font-weight:bold;font-size:1.1rem;color:#2c3e50;">📧 Contact Email *</label>
                        <input type="email" name="restaurant_email" value="<?php echo esc_attr($settings['restaurant_email'] ?? get_option('admin_email')); ?>"
                            style="width:100%;padding:15px;border:3px solid #e9ecef;border-radius:10px;font-size:1.1rem;box-sizing:border-box;" required>
                        <small style="color:#6c757d;display:block;margin-top:5px;">For reservation notifications</small>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:10px;font-weight:bold;font-size:1.1rem;color:#2c3e50;">📞 Phone Number</label>
                        <input type="tel" name="restaurant_phone" value="<?php echo esc_attr($settings['restaurant_phone'] ?? ''); ?>"
                            placeholder="+1 (555) 123-4567"
                            style="width:100%;padding:15px;border:3px solid #e9ecef;border-radius:10px;font-size:1.1rem;box-sizing:border-box;">
                        <small style="color:#6c757d;display:block;margin-top:5px;">Customer contact number</small>
                    </div>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;margin-bottom:10px;font-weight:bold;font-size:1.1rem;color:#2c3e50;">📍 Restaurant Address</label>
                    <input type="text" name="restaurant_address" value="<?php echo esc_attr($settings['restaurant_address'] ?? ''); ?>"
                        placeholder="123 Main Street, City, State 12345"
                        style="width:100%;padding:15px;border:3px solid #e9ecef;border-radius:10px;font-size:1.1rem;box-sizing:border-box;">
                </div>
            </div>

            <!-- Currency & Pricing -->
            <div style="margin-bottom:40px;padding:30px;background:#f8f9fa;border-radius:15px;border:3px solid #e9ecef;">
                <h2 style="color:#007cba;font-size:1.6rem;margin:0 0 25px 0;border-bottom:3px solid #007cba;padding-bottom:15px;">
                    💰 Currency and Pricing
                </h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;">
                    <div>
                        <label style="display:block;margin-bottom:10px;font-weight:bold;font-size:1.1rem;color:#2c3e50;">💱 Currency Symbol *</label>
                        <input type="text" name="currency_symbol" value="<?php echo esc_attr($settings['currency_symbol'] ?? '$'); ?>"
                            style="width:100%;padding:15px;border:3px solid #e9ecef;border-radius:10px;font-size:1.1rem;box-sizing:border-box;" maxlength="3" required>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:10px;font-weight:bold;font-size:1.1rem;color:#2c3e50;">💸 Base Price per Person</label>
                        <input type="number" name="base_price_per_person" min="0" step="0.01"
                            value="<?php echo esc_attr($settings['base_price_per_person'] ?? 0); ?>"
                            style="width:100%;padding:15px;border:3px solid #e9ecef;border-radius:10px;font-size:1.1rem;box-sizing:border-box;">
                    </div>
                </div>
            </div>

            <div style="text-align:right;">
                <button type="submit" class="button button-primary" style="padding:15px 45px;font-size:1.1rem;border-radius:8px;font-weight:bold;">💾 Save Settings</button>
            </div>
        </form>
    </div>
</div>
