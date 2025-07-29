<?php
if (!defined('ABSPATH')) exit;

// 1. Target date for grid (from ?date= param or today)
$chosen_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

// 2. Load all tables
$tables = class_exists('YRR_Tables_Model') ? YRR_Tables_Model::get_all() : array();

// 3. Get open/close times (from Hours Model or default)
if (class_exists('YRR_Hours_Model')) {
    $dayname = date('l', strtotime($chosen_date));
    $hours = YRR_Hours_Model::get_hours_for_day($dayname);
    $open  = ($hours && !empty($hours->open_time))  ? $hours->open_time  : '09:00';
    $close = ($hours && !empty($hours->close_time)) ? $hours->close_time : '23:00';
} else {
    $open  = '09:00';
    $close = '23:00';
}

// 4. Slot length in minutes (from settings)
$slot_duration = (class_exists('YRR_Settings_Model') && method_exists('YRR_Settings_Model', 'get_setting'))
    ? intval(YRR_Settings_Model::get_setting('slot_duration'))
    : 60; // Default 60 minutes

// 5. Build slot list
$slots = [];
for ($t = strtotime($open); $t < strtotime($close); $t += $slot_duration * 60) {
    $slots[] = date('H:i', $t);
}

// 6. Only load reservations for selected day (fixes memory bug!)
$reservations = class_exists('YRR_Reservation_Model')
    ? YRR_Reservation_Model::get_all(
        9999, 0, [
          'date_from' => $chosen_date,
          'date_to'   => $chosen_date
        ]
      )
    : array();

// 7. Index reservations for quick lookup: [table_id][slot_time] = reservation
$res_map = [];
foreach ($reservations as $r) {
    // Defensive: ensure table_id present
    if (!empty($r->table_id)) {
        $slot_time = date('H:i', strtotime($r->reservation_time));
        $res_map[$r->table_id][$slot_time] = $r;
    }
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Table Schedule', 'yrr'); ?></h1>
    <form method="get" style="margin-bottom:1em;display:flex;gap:1em;">
        <input type="hidden" name="page" value="yrr-schedule" />
        <label>
            <?php esc_html_e('Date:', 'yrr'); ?>
            <input type="date" name="date" value="<?php echo esc_attr($chosen_date); ?>" />
        </label>
        <button type="submit" class="button"><?php esc_html_e('Go', 'yrr'); ?></button>
    </form>
    <div class="yrr-table-schedule-wrap">
        <table class="yrr-table-schedule-grid widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Time', 'yrr'); ?></th>
                    <?php foreach($tables as $tbl): ?>
                        <th>
                          <?php echo esc_html($tbl->table_number); ?>
                          <?php if (!empty($tbl->location)) echo ' <small>' . esc_html($tbl->location) . '</small>'; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($slots as $slot): ?>
                <tr>
                    <td><?php echo esc_html(date('g:i A', strtotime($slot))); ?></td>
                    <?php foreach($tables as $tbl): ?>
                        <?php
                        $booking = $res_map[$tbl->id][$slot] ?? null;
                        $class = $booking
                            ? 'yrr-booked yrr-status-' . esc_attr($booking->status)
                            : 'yrr-available';
                        ?>
                        <td class="<?php echo $class; ?>">
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
