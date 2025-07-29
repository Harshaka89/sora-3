<?php
if (!defined('ABSPATH')) exit;

// 1. Get list of tables and their IDs
$tables = class_exists('YRR_Tables_Model') ? YRR_Tables_Model::get_all() : array();

// 2. Pick the displayed date
$chosen_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

// 3. Generate time slots for the day (e.g., every 30 min 09:00-23:00)
$hours = class_exists('YRR_Hours_Model') ? YRR_Hours_Model::get_hours_for_day(date('l', strtotime($chosen_date))) : null;
$open = $hours ? $hours->open_time : '09:00';
$close= $hours ? $hours->close_time : '23:00';
$slot_duration = class_exists('YRR_Settings_Model') ? YRR_Settings_Model::get_setting('slot_duration') : 60;
$slots = [];
for ($t = strtotime($open); $t < strtotime($close); $t += $slot_duration*60) {
    $slots[] = date('H:i', $t);
}

// 4. Get reservations for that day
$res = class_exists('YRR_Reservation_Model') ? YRR_Reservation_Model::get_all(['date_from'=>$chosen_date, 'date_to'=>$chosen_date]) : array();

// 5. Index reservations by [table_id, slot]
$res_map = [];
foreach($res as $r) {
    $hour = date('H:i', strtotime($r->reservation_time));
    $res_map[$r->table_id][$hour] = $r;
}
?>

<div class="wrap">
  <h1><?php esc_html_e('Table Schedule', 'yrr'); ?></h1>

  <!-- (Optional) Date picker -->
  <form method="get" action="">
    <input type="hidden" name="page" value="yrr-schedule" />
    <input type="date" name="date" value="<?php echo esc_attr($chosen_date); ?>" />
    <button type="submit" class="button"><?php esc_html_e('Go', 'yrr'); ?></button>
  </form>

  <div class="yrr-table-schedule-wrap">
    <table class="yrr-table-schedule-grid widefat striped">
      <thead>
        <tr>
          <th><?php esc_html_e('Time', 'yrr'); ?></th>
          <?php foreach($tables as $tbl): ?>
            <th><?php echo esc_html($tbl->table_number); ?><?php if(!empty($tbl->location)) echo ' <small>' . esc_html($tbl->location) . '</small>'; ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($slots as $slot): ?>
        <tr>
          <td><?php echo esc_html(date('g:i A', strtotime($slot))); ?></td>
          <?php foreach($tables as $tbl): ?>
            <?php $booking = $res_map[$tbl->id][$slot] ?? null; ?>
            <td class="<?php echo $booking ? 'yrr-booked yrr-status-' . esc_attr($booking->status) : 'yrr-available'; ?>">
              <?php if ($booking): ?>
                <strong><?php echo esc_html($booking->customer_name); ?></strong><br />
                <span><?php echo intval($booking->party_size); ?> <?php esc_html_e('guests', 'yrr'); ?></span><br />
                <small><?php echo ucfirst($booking->status); ?></small>
              <?php else: ?>
                <span class="yrr-slot-available"><?php esc_html_e('Available', 'yrr'); ?></span>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
