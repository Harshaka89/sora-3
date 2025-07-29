<?php
if (!defined('ABSPATH')) exit;

// Get the current week or the specified week from URL
$current_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($current_week)));
$week_end   = date('Y-m-d', strtotime('sunday this week', strtotime($current_week)));

// Fetch all reservations for the week
$reservations = class_exists('YRR_Reservation_Model')
    ? YRR_Reservation_Model::get_all(['date_from'=>$week_start, 'date_to'=>$week_end])
    : array();

// Organize reservations by day
$calendar_data = [];
for ($i=0; $i<7; $i++) {
    $dt = date('Y-m-d', strtotime($week_start . " +$i days"));
    $calendar_data[$dt] = [];
}
foreach ($reservations as $r) {
    if (isset($calendar_data[$r->reservation_date])) {
        $calendar_data[$r->reservation_date][] = $r;
    }
}

// Navigation
$prev_week = date('Y-m-d', strtotime($week_start . ' -7 days'));
$next_week = date('Y-m-d', strtotime($week_start . ' +7 days'));
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
?>

<div class="yrr-admin-wrap yrr-calendar-wrap">
    <h1 class="wp-heading-inline"><?php _e('Weekly Calendar', 'yrr'); ?></h1>
    <hr class="wp-header-end">

    <div class="yrr-calendar-header">
        <div class="yrr-calendar-nav">
            <a href="<?php echo admin_url('admin.php?page=yrr-weekly&week=' . $prev_week); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e('Previous Week', 'yrr'); ?>
            </a>
            <div class="yrr-current-week">
                <h2>
                    <?php echo date('F j',strtotime($week_start)); ?> – <?php echo date('F j, Y',strtotime($week_end)); ?>
                </h2>
                <a href="<?php echo admin_url('admin.php?page=yrr-weekly'); ?>" class="button button-secondary">
                    <?php _e('This Week', 'yrr'); ?>
                </a>
            </div>
            <a href="<?php echo admin_url('admin.php?page=yrr-weekly&week=' . $next_week); ?>" class="button">
                <?php _e('Next Week', 'yrr'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
    </div>

    <div class="yrr-calendar-container">
        <div class="yrr-calendar-grid">
            <!-- Time Column -->
            <div class="yrr-time-column">
                <div class="yrr-time-header"><?php _e('Time', 'yrr'); ?></div>
                <?php for($hour=9;$hour<=23;$hour++): ?>
                <div class="yrr-time-slot"><?php echo date('g A', strtotime("$hour:00")); ?></div>
                <?php endfor; ?>
            </div>
            <!-- Day Columns -->
            <?php for($i=0;$i<7;$i++):
                $dt = date('Y-m-d', strtotime($week_start . " +$i days"));
                $day_name = $days[$i];
                $day_res = $calendar_data[$dt];
                $is_today = ($dt === date('Y-m-d'));
            ?>
            <div class="yrr-day-column <?php echo $is_today?'yrr-today':''; ?>" data-date="<?php echo $dt; ?>">
                <div class="yrr-day-header">
                    <h3><?php echo $day_name; ?></h3>
                    <div class="yrr-day-date"><?php echo date('M j', strtotime($dt)); ?></div>
                </div>
                <div class="yrr-day-slots">
                    <?php for($hour=9;$hour<=23;$hour++):
                        $time_24 = sprintf('%02d:00:00', $hour);
                        $slots = array_filter($day_res, function($r) use ($hour) {
                            return intval(date('H', strtotime($r->reservation_time))) === $hour; });
                    ?>
                    <div class="yrr-time-slot-container" data-time="<?php echo $time_24; ?>">
                        <?php if (empty($slots)): ?>
                            <div class="yrr-empty-slot" tabindex="0"></div>
                        <?php else: foreach($slots as $r): ?>
                            <div class="yrr-reservation-block yrr-status-<?php echo esc_attr($r->status ?? 'pending'); ?>"
                                data-reservation-id="<?php echo esc_attr($r->id); ?>">
                                <div class="yrr-reservation-time"><?php echo date('g:i A', strtotime($r->reservation_time)); ?></div>
                                <div class="yrr-reservation-customer"><?php echo esc_html($r->customer_name ?? ''); ?></div>
                                <div class="yrr-reservation-party">👥 <?php echo intval($r->party_size ?? 0); ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>
