<?php
<?php include_once('../../includes/auth-check.php'); ?>
<a href="../../reset-password.php" class="btn btn-outline-secondary">Change Password</a>

if (!defined('ABSPATH')) exit;

// Ensure required models are loaded
if (!class_exists('YRR_Reservation_Model') && file_exists(YRR_PLUGIN_PATH . 'models/class-reservation-model.php')) {
    require_once YRR_PLUGIN_PATH . 'models/class-reservation-model.php';
}
if (!class_exists('YRR_Tables_Model') && file_exists(YRR_PLUGIN_PATH . 'models/class-tables-model.php')) {
    require_once YRR_PLUGIN_PATH . 'models/class-tables-model.php';
}

// You can adjust these values or fetch them from your database/models.
$res_count   = class_exists('YRR_Reservation_Model') ? YRR_Reservation_Model::get_total_count() : 0;
$cover_count = class_exists('YRR_Reservation_Model') ? YRR_Reservation_Model::count_covers() : 0;
$table_count = class_exists('YRR_Tables_Model') ? count(YRR_Tables_Model::get_all()) : 0;
$rev         = class_exists('YRR_Reservation_Model') ? YRR_Reservation_Model::total_revenue_this_month() : 0.00;

?>

<div class="yrr-admin-wrap yrr-dashboard-wrap">
  <h1 class="wp-heading-inline"><?php _e('Restaurant Dashboard', 'yrr'); ?></h1>
  <hr class="wp-header-end">
  <div class="yrr-dashboard-stats-grid">
    <div class="yrr-dashboard-card yrr-stat-reservations">
      <div class="yrr-stat-icon">📅</div>
      <div>
        <div class="yrr-stat-val"><?php echo $res_count; ?></div>
        <div class="yrr-stat-label"><?php _e('Reservations', 'yrr'); ?></div>
      </div>
    </div>
    <div class="yrr-dashboard-card yrr-stat-covers">
      <div class="yrr-stat-icon">👥</div>
      <div>
        <div class="yrr-stat-val"><?php echo $cover_count; ?></div>
        <div class="yrr-stat-label"><?php _e('Covers', 'yrr'); ?></div>
      </div>
    </div>
    <div class="yrr-dashboard-card yrr-stat-revenue">
      <div class="yrr-stat-icon">💰</div>
      <div>
        <div class="yrr-stat-val">$<?php echo number_format($rev, 2); ?></div>
        <div class="yrr-stat-label"><?php _e('Revenue (This Month)', 'yrr'); ?></div>
      </div>
    </div>
    <div class="yrr-dashboard-card yrr-stat-tables">
      <div class="yrr-stat-icon">🪑</div>
      <div>
        <div class="yrr-stat-val"><?php echo $table_count; ?></div>
        <div class="yrr-stat-label"><?php _e('Tables', 'yrr'); ?></div>
      </div>
    </div>
  </div>
  <div class="yrr-dashboard-actions">
    <a href="<?php echo admin_url('admin.php?page=yrr-reservations'); ?>" class="button button-primary"><?php _e('View Reservations', 'yrr'); ?></a>
    <a href="<?php echo admin_url('admin.php?page=yrr-settings'); ?>" class="button"><?php _e('Settings', 'yrr'); ?></a>
    <a href="<?php echo admin_url('admin.php?page=yrr-hours'); ?>" class="button"><?php _e('Hours', 'yrr'); ?></a>
    <a href="<?php echo admin_url('admin.php?page=yrr-tables'); ?>" class="button"><?php _e('Tables', 'yrr'); ?></a>
  </div>
</div>
