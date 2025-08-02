<?php require_once __DIR__ . '/../../includes/auth-check.php'; ?>
<a href="../../reset-password.php" class="btn btn-outline-secondary">Change Password</a>


if (!defined('ABSPATH')) exit;

// 1. Get current week start and end dates
$current_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($current_week)));
$week_end   = date('Y-m-d', strtotime('sunday this week', strtotime($current_week)));

// 2. Fetch reservations for this week
$reservations = class_exists('YRR_Reservation_Model')
    ? YRR_Reservation_Model::get_all(['date_from' => $week_start, 'date_to' => $week_end])
    : array();

// 3. Organize reservations by day
$calendar_data = array();
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime('+' . $i . ' days', strtotime($week_start)));
    $calendar_data[$date] = array();
}
foreach ($reservations as $res) {
    if (isset($calendar_data[$res->reservation_date])) {
        $calendar_data[$res->reservation_date][] = $res;
    }
}

// 4. Navigation links
$prev_week = date('Y-m-d', strtotime('-7 days', strtotime($week_start)));
$next_week = date('Y-m-d', strtotime('+7 days', strtotime($week_start)));
?>

<div class="wrap">
  <h1><?php esc_html_e('Weekly Calendar', 'yrr'); ?></h1>
  <hr class="wp-header-end" />

  <div class="yrr-calendar-navigation">
    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=yrr-weekly&week=' . $prev_week)); ?>">&laquo; <?php esc_html_e('Previous Week', 'yrr'); ?></a>
    <span class="yrr-calendar-current-week"><?php echo esc_html(date('M j, Y', strtotime($week_start)) . ' - ' . date('M j, Y', strtotime($week_end))); ?></span>
    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=yrr-weekly')); ?>"><?php esc_html_e('Current Week', 'yrr'); ?></a>
    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=yrr-weekly&week=' . $next_week)); ?>"><?php esc_html_e('Next Week', 'yrr'); ?> &raquo;</a>
  </div>

  <div class="yrr-calendar-grid">
    <div class="yrr-time-column">
      <div class="yrr-time-header"><?php esc_html_e('Time', 'yrr'); ?></div>
      <?php for ($hour = 9; $hour <= 23; $hour++) : ?>
        <div class="yrr-time-slot"><?php echo esc_html(date('g A', strtotime($hour . ':00'))); ?></div>
      <?php endfor; ?>
    </div>

    <?php for ($i = 0; $i < 7; $i++) : 
      $date = date('Y-m-d', strtotime('+' . $i . ' days', strtotime($week_start)));
      $day_name = $days[$i];
      $is_today = (date('Y-m-d') === $date);
      $day_reservations = $calendar_data[$date] ?? array();
    ?>
      <div class="yrr-day-column <?php echo $is_today ? 'yrr-today' : ''; ?>" data-date="<?php echo esc_attr($date); ?>">
        <div class="yrr-day-header">
          <strong><?php echo esc_html($day_name); ?></strong> <br />
          <small><?php echo esc_html(date('M j', strtotime($date))); ?></small>
        </div>
        <div class="yrr-day-slots">
          <?php for ($hour = 9; $hour <= 23; $hour++) :
            $slot_time = sprintf('%02d:00:00', $hour);
            $slots = array_filter($day_reservations, function($res) use ($hour) {
              return intval(date('H', strtotime($res->reservation_time))) === $hour;
            });
          ?>
            <div class="yrr-time-slot-container" data-time="<?php echo esc_attr($slot_time); ?>">
              <?php if (empty($slots)) : ?>
                <div class="yrr-empty-slot" tabindex="0" aria-label="<?php esc_attr_e('Available slot', 'yrr'); ?>">&nbsp;</div>
              <?php else : ?>
                <?php foreach ($slots as $slot) : ?>
                  <div class="yrr-reservation-block yrr-status-<?php echo esc_attr($slot->status ?? 'pending'); ?>" data-reservation-id="<?php echo esc_attr($slot->id); ?>">
                    <span class="yrr-reservation-time"><?php echo esc_html(date('g:i A', strtotime($slot->reservation_time))); ?></span>
                    <span class="yrr-reservation-name"><?php echo esc_html($slot->customer_name); ?></span>
                    <span class="yrr-reservation-party"><?php echo esc_html($slot->party_size); ?> <?php esc_html_e('guests', 'yrr'); ?></span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          <?php endfor; ?>
        </div>
      </div>
    <?php endfor; ?>

  </div>
</div>
