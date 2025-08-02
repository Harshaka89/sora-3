
<?php

if (!defined('ABSPATH')) exit;

// Ensure all dynamic output is properly escaped using WordPress helper functions.
?>

// 1. Get selected date (?date=..., or today)
$current_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');

// 2. Load all tables for the schedule grid
$tables = class_exists('YRR_Tables_Model') ? YRR_Tables_Model::get_all() : array();

// 3. Get operating hours (model or fallback)
if (class_exists('YRR_Hours_Model')) {
    $dayname = date('l', strtotime($current_date));
    $hours = YRR_Hours_Model::get_hours_for_day($dayname);
    $open  = !empty($hours->open_time) ? $hours->open_time : '09:00';
    $close = !empty($hours->close_time) ? $hours->close_time : '23:00';
} else {
    $open  = '09:00';
    $close = '23:00';
}

// 4. Slot duration (in minutes, from settings or fallback 60)
$slot_duration = (class_exists('YRR_Settings_Model') && method_exists('YRR_Settings_Model', 'get_setting'))
    ? intval(YRR_Settings_Model::get_setting('slot_duration'))
    : 60;
// 5. Build slot array (e.g. ['09:00', '10:00', ...])
$time_slots = [];
/////////////////////////////////
//for ($t = strtotime($open); $t < strtotime($close); $t += $slot_duration * 60) {
   // $time_slots[] = [
     //   'time' => date('H:i', $t),
   //     'formatted_time' => date('g:i A', $t),
  //
  //   ];
//}
$time_slots = [
  ['time' => '12:00', 'formatted_time' => '12:00 PM']
];


// 6. Memory-safe: only load THIS DAY'S reservations!
$reservations = class_exists('YRR_Reservation_Model')
    ? YRR_Reservation_Model::get_all(
        9999, 0,
        ['date_from' => $current_date, 'date_to' => $current_date]
    )
    : array();

// 7. Build reservation lookup [table_id][slot] = booking
$res_map = [];
foreach ($reservations as $r) {
    if (!empty($r->table_id)) {
        $slot_time = date('H:i', strtotime($r->reservation_time));
        $res_map[$r->table_id][$slot_time] = $r;
    }
}

// 8. Helper: booking status color
function yenolx_booking_color($status) {
    switch ($status) {
        case 'confirmed': return '#28a745';
        case 'pending':   return '#ffc107';
        case 'cancelled': return '#dc3545';
        default:          return '#6c757d';
    }
}
?>

<div class="wrap">
    <div style="max-width: 1800px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
        <!-- Header: Date Controls + Legend -->
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="font-size:2.0rem; color:#2c3e50;">📅 Table Schedule &amp; Time Slots</h1>
            <p style="color:#6c757d; margin:10px 0 0;">Grid for <?php echo esc_html( date('F j, Y', strtotime($current_date)) ); ?></p>
            <?php
                $prev_date = date('Y-m-d', strtotime($current_date.' -1 day'));
                $next_date = date('Y-m-d', strtotime($current_date.' +1 day'));
            ?>
            <div style="margin:15px 0;">
                <a href="<?php echo esc_url( '?page=yrr-schedule&date=' . $prev_date ); ?>" class="button button-primary">← Previous</a>
                <input type="date" value="<?php echo esc_attr($current_date); ?>"
                       onchange="window.location.href='?page=yrr-schedule&date='+this.value"
                       style="margin:0 10px; padding:5px 10px; border-radius:5px;" />
                <a href="?page=yrr-schedule" class="button button-success">Today</a>
                <a href="<?php echo esc_url( '?page=yrr-schedule&date=' . $next_date ); ?>" class="button button-primary">Next →</a>
            </div>
            <div style="margin:20px 0">
                <span style="background:#28a745;color:white;padding:4px 12px;border-radius:12px;font-weight: bold;margin-right:10px;">✅ Confirmed</span>
                <span style="background:#ffc107;color:black;padding:4px 12px;border-radius:12px;font-weight: bold;margin-right:10px;">⏳ Pending</span>
                <span style="background:#dc3545;color:white;padding:4px 12px;border-radius:12px;font-weight: bold;margin-right:10px;">❌ Cancelled</span>
                <span style="background:#f8f9fa;color:#333;padding:4px 12px;border-radius:12px;border:1px solid #dee2e6;font-weight: bold;">🆓 Available</span>
            </div>
        </div>

        <!-- Grid Table -->
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; min-width:1000px;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#007cba 60%,#20c997 100%);color:white;">
                        <th style="padding:14px;min-width:100px;text-align:left;">Table</th>
                        <?php foreach ($time_slots as $slot): ?>
                            <th style="padding:6px 3px;text-align:center;min-width:75px;font-size:0.92rem;">
                                <?php echo esc_html($slot['formatted_time']); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $tbl): ?>
                        <tr>
                            <td style="background:#f8f9fa;font-weight: bold;vertical-align: middle;">
                                🍽️ <?php echo esc_html($tbl->table_number); ?>
                <div style="font-size:0.88rem;color:#6c757d;">👥 <?php echo esc_html( intval( $tbl->capacity ) ); ?> seats</div>
                                <?php if (!empty($tbl->location)): ?>
                                    <div style="font-size:0.82rem;color:#6c757d;">📍 <?php echo esc_html($tbl->location); ?></div>
                                <?php endif; ?>
                            </td>
                            <?php
                            foreach ($time_slots as $slot):
                                $slot_time = $slot['time'];
                                $booking = $res_map[$tbl->id][$slot_time] ?? null;
                            ?>
                            <td style="text-align:center;padding:4px;">
                                <?php if ($booking):?>
                                    <div onclick="yenolxShowBookingDetails(<?php echo esc_js( wp_json_encode( $booking ) ); ?>)"
                                         style="background:<?php echo esc_attr( yenolx_booking_color( $booking->status ) ); ?>;color:white;padding:5px 1px;border-radius:7px;cursor:pointer;font-size:0.83rem;font-weight:600;">
                                        <?php echo esc_html(mb_substr($booking->customer_name,0,10)); ?><br>
                                        <span style="font-size:0.73rem;">👥 <?php echo esc_html( intval( $booking->party_size ) ); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div onclick="yenolxQuickBook('<?php echo esc_js( $tbl->id ); ?>','<?php echo esc_js( $current_date ); ?>','<?php echo esc_js( $slot_time ); ?>')"
                                         style="background: #f8f9fa; border:1.5px dashed #dee2e6; padding:5px 1px; border-radius:7px; cursor:pointer; color:#6c757d; font-size:0.75rem;">
                                        🆓<br>Available
                                    </div>
                                <?php endif;?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Quick Stats Row -->
        <div style="margin-top:30px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px;">
            <?php
            $total = $conf = $pend = 0;
            foreach ($reservations as $r) {
                $total++;
                if ($r->status === 'confirmed') $conf++;
                if ($r->status === 'pending') $pend++;
            }
            ?>
            <div style="background:linear-gradient(90deg,#28a745 0%,#20c997 100%);color:white;padding:18px;border-radius:10px;text-align:center;">
                <span style="font-size:1.8rem;font-weight:bold;"><?php echo esc_html( count( $tables ) ); ?></span><br>Total Tables
            </div>
            <div style="background:linear-gradient(90deg,#28a745,#17a2b8);color:white;padding:18px;border-radius:10px;text-align:center;">
                <span style="font-size:1.8rem;font-weight:bold;"><?php echo esc_html( $conf ); ?></span><br>Confirmed
            </div>
            <div style="background:linear-gradient(90deg,#ffc107,#fd7e14);color:white;padding:18px;border-radius:10px;text-align:center;">
                <span style="font-size:1.8rem;font-weight:bold;"><?php echo esc_html( $pend ); ?></span><br>Pending
            </div>
            <div style="background:linear-gradient(90deg,#007cba,#17a2b8);color:white;padding:18px;border-radius:10px;text-align:center;">
                <span style="font-size:1.8rem;font-weight:bold;"><?php echo esc_html( $total ); ?></span><br>Total Bookings
            </div>
        </div>
    </div>
</div>

<!-- Booking Details Modal & Quick Book Modal -->
<div id="yenolxBookingDetailsModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.84);z-index:99999;align-items:center;justify-content:center;">
  <div style="background:#fff;padding:30px;border-radius:14px;max-width:480px;width:90%;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;border-bottom:1.5px solid #eee;">
      <h3 style="margin:0;">Booking Details</h3>
      <button onclick="document.getElementById('yenolxBookingDetailsModal').style.display='none'" style="font-size:24px;border:none;background:none;color:#6c757d;cursor:pointer;">×</button>
    </div>
    <div id="yenolxBookingDetailsContent"></div>
  </div>
</div>
<div id="yenolxQuickBookModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.84);z-index:99999;align-items:center;justify-content:center;">
  <div style="background:#fff;padding:30px;border-radius:14px;max-width:460px;width:90%;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;border-bottom:1.5px solid #eee;">
      <h3 style="margin:0;">Quick Book Table</h3>
      <button onclick="document.getElementById('yenolxQuickBookModal').style.display='none'" style="font-size:24px;border:none;background:none;color:#6c757d;cursor:pointer;">×</button>
    </div>
    <form method="post" action="<?php echo esc_url( admin_url('admin.php?page=yrr-reservations') ); ?>">
      <?php wp_nonce_field('create_manual_reservation','manual_reservation_nonce'); ?>
      <input type="hidden" name="create_manual_reservation" value="1">
      <input type="hidden" id="yenolx_quick_table_id" name="table_id">
      <input type="hidden" id="yenolx_quick_date" name="reservation_date">
      <input type="hidden" id="yenolx_quick_time" name="reservation_time">
      <label>Name*<input type="text" name="customer_name" required style="width:100%;margin-bottom:13px;" /></label>
      <label>Email*<input type="email" name="customer_email" required style="width:100%;margin-bottom:13px;" /></label>
      <label>Phone*<input type="tel" name="customer_phone" required style="width:100%;margin-bottom:13px;" /></label>
      <label>Party Size*<select name="party_size" style="width:100%;margin-bottom:13px;"><?php for($i=1;$i<=12;$i++): ?><option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?> guest<?php echo $i!=1?'s':'';?></option><?php endfor; ?></select></label>
      <label>Special Requests<textarea name="special_requests" rows="1" style="width:100%;margin-bottom:15px;"></textarea></label>
      <div style="text-align:right;"><button type="submit" style="background:linear-gradient(90deg,#28a745,#20c997);color:white;border:none;padding:10px 18px;border-radius:8px;">Book Table</button></div>
    </form>
  </div>
</div>

<script>
function yenolxShowBookingDetails(booking) {
  var modal = document.getElementById('yenolxBookingDetailsModal');
  var content = document.getElementById('yenolxBookingDetailsContent');
  content.innerHTML = `
    <div style="margin-bottom:18px;">
      <span style="background:${{
        confirmed: '#28a745', pending: '#ffc107', cancelled:'#dc3545'
      }[booking.status]||'#6c757d'};color:white;padding:6px 18px;border-radius:12px;font-weight:bold;text-transform:uppercase;">${booking.status}</span>
    </div>
    <div><strong>Name: </strong>${booking.customer_name}</div>
    <div><strong>Party Size: </strong>${booking.party_size}</div>
    <div><strong>Email: </strong>${booking.customer_email||''}</div>
    <div><strong>Phone: </strong>${booking.customer_phone||''}</div>
    <div><strong>Date: </strong>${booking.reservation_date}</div>
    <div><strong>Time: </strong>${booking.reservation_time}</div>
    ${booking.special_requests ? '<div><strong>Requests: </strong>' + booking.special_requests + '</div>' : ''}
    <div style="margin-top:18px;text-align:center;">
      <a href="<?php echo esc_url( admin_url('admin.php?page=yrr-reservations') ); ?>" class="button button-primary">View All Reservations</a>
    </div>
  `;
  modal.style.display = 'flex';
}
function yenolxQuickBook(tableId,date,time) {
  document.getElementById('yenolx_quick_table_id').value=tableId;
  document.getElementById('yenolx_quick_date').value=date;
  document.getElementById('yenolx_quick_time').value=time;
  document.getElementById('yenolxQuickBookModal').style.display='flex';
}
// Modal close by click-outside
document.getElementById('yenolxBookingDetailsModal').onclick = function(e){
  if(e.target===this)this.style.display='none';
};
document.getElementById('yenolxQuickBookModal').onclick = function(e){
  if(e.target===this)this.style.display='none';
};
</script>
<style>
@media (max-width: 1200px) { table{font-size:0.88rem;} th,td{padding:3px!important;} }
@media (max-width: 800px) { th,td{min-width:45px!important;} }
@media (max-width: 800px) { div[style*="grid-template-columns"]{grid-template-columns:1fr!important;} }
</style>
