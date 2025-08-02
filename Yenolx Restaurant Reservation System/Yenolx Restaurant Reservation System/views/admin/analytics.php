<?php include_once('../../includes/auth-check.php'); ?>
<a href="../../reset-password.php" class="btn btn-outline-secondary">Change Password</a>

if (!defined('ABSPATH')) exit;

// EXAMPLE: Fetch summary stats (replace with YRR_Reservation_Model helpers)
$stats = class_exists('YRR_Reservation_Model') ? YRR_Reservation_Model::get_dashboard_stats() : [];
// EXAMPLE: 14 days chart
$booking_days = [];
if (class_exists('YRR_Reservation_Model')) {
  $start = date('Y-m-d', strtotime('-13 days'));
  $end = date('Y-m-d');
  $bookings = YRR_Reservation_Model::get_by_date_range($start, $end);
  foreach ($bookings as $b) {
    $d = $b->reservation_date;
    $booking_days[$d] = ($booking_days[$d] ?? 0) + 1;
  }
}
$labels = []; $bookings_data = [];
for ($i=13;$i>=0;$i--) {
  $d = date('Y-m-d', strtotime("-$i days"));
  $labels[] = $d;
  $bookings_data[] = $booking_days[$d] ?? 0;
}
?>
<div class="wrap">
  <div style="max-width:1280px;margin:22px auto 30px auto;background:white;padding:24px 0 30px 0;border-radius:18px;box-shadow:0 7px 22px rgba(0,0,0,0.10);">
    <div style="text-align:center;margin-bottom:17px;padding-bottom:13px;border-bottom:3px solid #007cba;">
      <h1 style="font-size:2rem;color:#213254;margin:0;">📊 Analytics & Reports</h1>
      <p style="color:#6c757d;margin:5px 0 0;font-size:1.08rem;">Business insights at a glance (Bookings, Guests, Revenue, Trends)</p>
    </div>
    <!-- KPI Cards -->
    <div style="display:grid;grid-template-columns:repeat(4,minmax(170px,1fr));gap:24px;margin:0 28px 37px 28px;">
      <div style="background:linear-gradient(135deg,#007cba 0%,#43bbf4 100%);color:white;padding:27px 8px 19px 8px;border-radius:12px;text-align:center;">
        <div style="font-size:2.2rem;margin-bottom:7px;">📅</div>
        <div style="font-size:2.0rem;font-weight:900;"><?php echo intval($stats['total'] ?? 0); ?></div>
        <div style="font-size:1.02rem;opacity:0.92;">Reservations</div>
      </div>
      <div style="background:linear-gradient(135deg,#28a745 0%,#20c997 100%);color:white;padding:27px 8px 19px 8px;border-radius:12px;text-align:center;">
        <div style="font-size:2.2rem;margin-bottom:7px;">👥</div>
        <div style="font-size:2.0rem;font-weight:900;"><?php echo intval($stats['today_guests'] ?? 0); ?></div>
        <div style="font-size:1.02rem;opacity:0.92;">Guests Today</div>
      </div>
      <div style="background:linear-gradient(135deg,#ffc107 0%,#e0a800 100%);color:white;padding:27px 8px 19px 8px;border-radius:12px;text-align:center;">
        <div style="font-size:2.2rem;margin-bottom:7px;">💰</div>
        <div style="font-size:2.0rem;font-weight:900;"><?php echo number_format($stats['revenue'] ?? 0,2); ?></div>
        <div style="font-size:1.02rem;opacity:0.92;">Revenue</div>
      </div>
      <div style="background:linear-gradient(135deg,#dc3545 0%,#b21f2d 100%);color:white;padding:27px 8px 19px 8px;border-radius:12px;text-align:center;">
        <div style="font-size:2.2rem;margin-bottom:7px;">❌</div>
        <div style="font-size:2.0rem;font-weight:900;"><?php echo intval($stats['pending'] ?? 0); ?></div>
        <div style="font-size:1.02rem;opacity:0.92;">Pending/Cancel</div>
      </div>
    </div>
    <!-- Booking Chart -->
    <div style="margin:31px 36px;">
      <canvas id="yrrBookingChart" height="90"></canvas>
      <div style="color:#6c757d;font-size:1rem;text-align:center;margin-top:9px;">Bookings per Day (last 14 days)</div>
    </div>
    <hr style="margin:35px 0 23px 0;">
    <!-- Additional Panels -->
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:31px;margin:0 33px;">
      <div style="background:#f8f9fa;padding:19px 14px 17px 14px;border-radius:12px;">
        <h3 style="font-size:1.07rem;margin-top:0;color:#213254;margin-bottom:14px;">Top Tables</h3>
        <ul style="padding-left:18px;color:#232a2f;font-size:1.04em;line-height:1.6;">
          <li>Table 4 - 19x booked (example)</li>
          <li>Table 2 - 15x booked</li>
          <li>Table 6 - 12x booked</li>
          <li>Table 1 - 10x booked</li>
        </ul>
      </div>
      <div style="background:#f8f9fa;padding:19px 14px 17px 14px;border-radius:12px;">
        <h3 style="font-size:1.07rem;margin-top:0;color:#213254;margin-bottom:14px;">Top Coupons</h3>
        <ul style="padding-left:18px;color:#232a2f;font-size:1.04em;line-height:1.6;">
          <li>DEAL20 - 7 uses (example)</li>
          <li>VIP10 - 5 uses</li>
        </ul>
      </div>
    </div>
    <div style="text-align:center;margin:38px auto 0;">
      <a href="<?php echo admin_url('admin.php?page=yrr-dashboard');?>" style="background:linear-gradient(135deg,#007cba 0%,#43bbf4 100%);color:white;padding:14px 30px;text-decoration:none;border-radius:11px;font-weight:900;font-size:1.08em;margin-top:12px;">← Back to Dashboard</a>
    </div>
  </div>
</div>
<!-- Chart.js for Chart Rendering -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
  var ctx = document.getElementById('yrrBookingChart').getContext('2d');
  var chart = new Chart(ctx, {
    type:'line',
    data:{
      labels: <?php echo json_encode($labels); ?>,
      datasets:[{
        label:'Reservations',
        data:<?php echo json_encode($bookings_data);?>,
        borderColor:'#007cba',
        backgroundColor:'rgba(67,187,244,0.14)',
        borderWidth:3,
        pointRadius:3,
        fill:true,
        tension:0.33
      }]
    },
    options:{
      plugins:{legend:{display:false}},
      scales:{
        x:{grid:{display:false},ticks:{color:'#344360'}},
        y:{beginAtZero:true,grid:{color:'#dee2e6'},ticks:{color:'#344360'}}
      }
    }
  });
});
</script>
<style>
@media (max-width:900px){
  div[style*="grid-template-columns:repeat(4,minmax(170px,1fr))"]{grid-template-columns:repeat(2,minmax(170px,1fr))!important;}
  div[style*="grid-template-columns:repeat(2,1fr)"]{grid-template-columns:1fr!important;}
}
canvas#yrrBookingChart{max-width:100%;display:block;margin:auto;}
</style>
