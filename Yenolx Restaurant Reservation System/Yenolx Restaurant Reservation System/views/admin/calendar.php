<?php
<?php include_once('../../includes/auth-check.php'); ?>
<a href="../../reset-password.php" class="btn btn-outline-secondary">Change Password</a>

/**
 * Weekly Calendar Admin View - Clean and Professional
 * (assets/css/admin-calendar.css, assets/js/admin-calendar.js must be loaded for full UI)
 */

if (!defined('ABSPATH')) { exit('Direct access forbidden.'); }

// Get week range
$current_week = isset($_GET['week']) ? sanitize_text_field($_GET['week']) : date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($current_week)));
$week_end   = date('Y-m-d', strtotime('sunday this week', strtotime($current_week)));
// Location filter (if enabled)
$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;

// Reservation filters
$filters = array('date_from'=>$week_start, 'date_to'=>$week_end);
if ($location_id) $filters['location_id'] = $location_id;

// Fetch data (models must exist)
$reservations = class_exists('YRR_Reservation_Model') ? YRR_Reservation_Model::get_all($filters) : array();
$locations = class_exists('YRR_Locations_Model') ? YRR_Locations_Model::get_all(true) : array();

// Build calendar data (reservations per day)
$calendar_data = array();
for ($i = 0; $i < 7; $i++) {
    $d = date('Y-m-d', strtotime($week_start . " +$i days"));
    $calendar_data[$d] = array();
}
foreach ($reservations as $reservation) {
    $d = $reservation->reservation_date;
    if (isset($calendar_data[$d])) $calendar_data[$d][] = $reservation;
}
// Stats
$total_covers = 0;
$total_revenue = 0;
foreach ($calendar_data as $day_res) {
    foreach ($day_res as $r) {
        $total_covers += intval($r->party_size ?? 0);
        $total_revenue += floatval($r->final_price ?? 0);
    }
}
$avg_per_cover = ($total_covers > 0) ? $total_revenue/$total_covers : 0;

// Week nav
$prev_week = date('Y-m-d', strtotime($week_start.' -7 days'));
$next_week = date('Y-m-d', strtotime($week_start.' +7 days'));
$days = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
?>
<div class="yrr-admin-wrap yrr-calendar-wrap">
    <h1 class="wp-heading-inline"><?php _e('Reservations Calendar', 'yrr'); ?></h1>
    <a href="#" class="page-title-action yrr-btn-create-reservation">
        <span class="dashicons dashicons-plus-alt"></span>
        <?php _e('Add New Reservation', 'yrr'); ?>
    </a>
    <hr class="wp-header-end">
    <div class="yrr-calendar-header">
        <div class="yrr-calendar-nav">
            <a href="<?php echo admin_url('admin.php?page=yrr-calendar&week='.$prev_week.($location_id?"&location_id=".$location_id:'') ); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e('Previous Week', 'yrr'); ?>
            </a>
            <div class="yrr-current-week">
                <h2><?php echo date('F j',strtotime($week_start)).' - '.date('F j, Y',strtotime($week_end)); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=yrr-calendar'.($location_id?"&location_id=".$location_id:'')); ?>" class="button button-secondary">
                    <?php _e('This Week', 'yrr'); ?>
                </a>
            </div>
            <a href="<?php echo admin_url('admin.php?page=yrr-calendar&week='.$next_week.($location_id?"&location_id=".$location_id:'') ); ?>" class="button">
                <?php _e('Next Week', 'yrr'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
        <?php if(count($locations)>1): ?>
        <div>
            <form method="get" class="yrr-location-filter" style="margin:0;">
                <input type="hidden" name="page" value="yrr-calendar">
                <input type="hidden" name="week" value="<?php echo esc_attr($current_week); ?>">
                <label for="location_filter"><?php _e('Location:', 'yrr'); ?></label>
                <select name="location_id" id="location_filter" onchange="this.form.submit()">
                    <option value=""><?php _e('All Locations', 'yrr'); ?></option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo esc_attr($loc->id); ?>" <?php selected($location_id, $loc->id); ?>>
                            <?php echo esc_html($loc->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <div class="yrr-week-stats">
        <div class="yrr-stats-grid">
            <div class="yrr-stat-card"><div class="yrr-stat-icon">📅</div>
                <div class="yrr-stat-content"><h3><?php echo count($reservations); ?></h3><p><?php _e('Reservations', 'yrr'); ?></p></div>
            </div>
            <div class="yrr-stat-card"><div class="yrr-stat-icon">👥</div>
                <div class="yrr-stat-content"><h3><?php echo $total_covers; ?></h3><p><?php _e('Covers', 'yrr'); ?></p></div>
            </div>
            <div class="yrr-stat-card"><div class="yrr-stat-icon">💰</div>
                <div class="yrr-stat-content"><h3>$<?php echo number_format($total_revenue, 2); ?></h3><p><?php _e('Revenue', 'yrr'); ?></p></div>
            </div>
            <div class="yrr-stat-card"><div class="yrr-stat-icon">📈</div>
                <div class="yrr-stat-content"><h3>$<?php echo number_format($avg_per_cover,2); ?></h3><p><?php _e('Avg/Cover', 'yrr'); ?></p></div>
            </div>
        </div>
    </div>
    <div class="yrr-calendar-container">
        <div class="yrr-calendar-grid">
            <!-- Time Column -->
            <div class="yrr-time-column"><div class="yrr-time-header"><?php _e('Time', 'yrr'); ?></div>
                <?php for($hour=9;$hour<=23;$hour++): ?>
                <div class="yrr-time-slot"><?php echo date('g A', strtotime("$hour:00")); ?></div>
                <?php endfor; ?>
            </div>
            <!-- Day Columns -->
            <?php for($i=0;$i<7;$i++):
                $dt = date('Y-m-d', strtotime($week_start." +$i days"));
                $day_name = $days[$i];
                $day_res = $calendar_data[$dt];
                $day_covers = array_sum(array_map(function($r){return intval($r->party_size ?? 0);},$day_res));
                $day_rev = array_sum(array_map(function($r){return floatval($r->final_price ?? 0);},$day_res));
                $is_today = ($dt === date('Y-m-d'));
            ?>
            <div class="yrr-day-column <?php echo $is_today?'yrr-today':''; ?>" data-date="<?php echo $dt; ?>">
                <div class="yrr-day-header">
                    <h3><?php echo $day_name; ?></h3>
                    <div class="yrr-day-date"><?php echo date('M j', strtotime($dt)); ?></div>
                    <div class="yrr-day-stats">
                        <span class="yrr-day-covers"><?php echo $day_covers; ?> covers</span>
                        <span class="yrr-day-revenue">$<?php echo number_format($day_rev, 0); ?></span>
                    </div>
                </div>
                <div class="yrr-day-slots">
                    <?php for($hour=9;$hour<=23;$hour++):
                        $time_24 = sprintf('%02d:00:00', $hour);
                        $slots = array_filter($day_res, function($r) use ($time_24, $hour) {
                            $h = date('H', strtotime($r->reservation_time));
                            return intval($h) === $hour; });
                    ?>
                    <div class="yrr-time-slot-container" data-time="<?php echo $time_24; ?>">
                        <?php if (empty($slots)): ?>
                            <div class="yrr-empty-slot" tabindex="0" aria-label="<?php echo esc_attr(__('Create new reservation', 'yrr')); ?>" onclick="YRR_Calendar.createReservation('<?php echo $dt; ?>','<?php echo $time_24; ?>')">
                                <span class="yrr-add-icon">+</span>
                            </div>
                        <?php else: foreach($slots as $r): ?>
                            <div class="yrr-reservation-block yrr-status-<?php echo esc_attr($r->status ?? 'pending'); ?>"
                                data-reservation-id="<?php echo esc_attr($r->id); ?>"
                                tabindex="0" aria-label="<?php echo esc_attr(__('View reservation details', 'yrr')); ?>"
                                onclick="YRR_Calendar.showReservationDetails(<?php echo esc_attr($r->id); ?>)">
                                <div class="yrr-reservation-time"><?php echo date('g:i A', strtotime($r->reservation_time)); ?></div>
                                <div class="yrr-reservation-customer"><?php echo esc_html($r->customer_name ?? ''); ?></div>
                                <div class="yrr-reservation-party">👥 <?php echo intval($r->party_size ?? 0); ?><?php if(!empty($r->table_number)):?> • 🪑 <?php echo esc_html($r->table_number); ?><?php endif;?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <div class="yrr-calendar-legend">
        <h4><?php _e('Status Legend', 'yrr'); ?></h4>
        <div class="yrr-legend-items">
            <div class="yrr-legend-item"><div class="yrr-legend-color yrr-status-pending"></div><span><?php _e('Pending', 'yrr'); ?></span></div>
            <div class="yrr-legend-item"><div class="yrr-legend-color yrr-status-confirmed"></div><span><?php _e('Confirmed', 'yrr'); ?></span></div>
            <div class="yrr-legend-item"><div class="yrr-legend-color yrr-status-completed"></div><span><?php _e('Completed', 'yrr'); ?></span></div>
            <div class="yrr-legend-item"><div class="yrr-legend-color yrr-status-cancelled"></div><span><?php _e('Cancelled', 'yrr'); ?></span></div>
        </div>
    </div>
</div>

<!-- Reservation Details Modal -->
<div id="yrr-reservation-details-modal" class="yrr-modal" style="display:none;">
    <div class="yrr-modal-overlay"></div>
    <div class="yrr-modal-content">
        <div class="yrr-modal-header">
            <h2><?php _e('Reservation Details', 'yrr'); ?></h2>
            <button type="button" class="yrr-modal-close">&times;</button>
        </div>
        <div class="yrr-modal-body" id="yrr-reservation-details-content"></div>
        <div class="yrr-modal-footer">
            <button type="button" class="button yrr-modal-close"><?php _e('Close', 'yrr'); ?></button>
            <button type="button" class="button button-primary" id="yrr-edit-reservation"><?php _e('Edit Reservation', 'yrr'); ?></button>
        </div>
    </div>
</div>
<!-- New Reservation Modal Inclusion -->
<?php
$modal_path = YRR_PLUGIN_PATH.'views/admin/partials/new-reservation-modal.php';
if (file_exists($modal_path)) include $modal_path; 
?>
<!-- External CSS/JS Required: 
     - assets/css/admin-calendar.css 
     - assets/js/admin-calendar.js 
     (See admin asset enqueue in your plugin setup) -->
