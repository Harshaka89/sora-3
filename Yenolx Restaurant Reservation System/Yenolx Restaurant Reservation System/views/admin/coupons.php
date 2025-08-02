<?php require_once __DIR__ . '/../../includes/auth-check.php'; ?>
<a href="../../reset-password.php" class="btn btn-outline-secondary">Change Password</a>

if (!defined('ABSPATH')) exit;

// ADD coupon
if (!empty($_POST['add_coupon']) && wp_verify_nonce($_POST['coupon_nonce'], 'yrr_coupon_action')) {
    $data = [
        'code'           => strtoupper(sanitize_text_field($_POST['code'])),
        'discount_type'  => sanitize_text_field($_POST['discount_type']),
        'discount_value' => floatval($_POST['discount_value']),
        'minimum_amount' => floatval($_POST['minimum_amount']),
        'usage_limit'    => intval($_POST['usage_limit']),
        'valid_from'     => sanitize_text_field($_POST['valid_from']),
        'valid_to'       => sanitize_text_field($_POST['valid_to']),
        'is_active'      => !empty($_POST['is_active']) ? 1 : 0,
    ];
    if (class_exists('YRR_Coupons_Model')) YRR_Coupons_Model::create($data);
    wp_redirect(admin_url('admin.php?page=yrr-coupons&message=coupon_added')); exit;
}
// EDIT coupon
if (!empty($_POST['update_coupon']) && wp_verify_nonce($_POST['coupon_nonce'], 'yrr_coupon_action')) {
    $id = intval($_POST['coupon_id']);
    $data = [
        'code'           => strtoupper(sanitize_text_field($_POST['code'])),
        'discount_type'  => sanitize_text_field($_POST['discount_type']),
        'discount_value' => floatval($_POST['discount_value']),
        'minimum_amount' => floatval($_POST['minimum_amount']),
        'usage_limit'    => intval($_POST['usage_limit']),
        'valid_from'     => sanitize_text_field($_POST['valid_from']),
        'valid_to'       => sanitize_text_field($_POST['valid_to']),
        'is_active'      => !empty($_POST['is_active']) ? 1 : 0,
    ];
    if ($id && class_exists('YRR_Coupons_Model')) YRR_Coupons_Model::update($id, $data);
    wp_redirect(admin_url('admin.php?page=yrr-coupons&message=coupon_updated')); exit;
}
// DELETE coupon
if (isset($_GET['delete_coupon']) && check_admin_referer('yrr_coupon_action')) {
    $id = intval($_GET['delete_coupon']);
    if ($id && class_exists('YRR_Coupons_Model')) YRR_Coupons_Model::delete($id);
    wp_redirect(admin_url('admin.php?page=yrr-coupons&message=coupon_deleted')); exit;
}
// Fetch coupons
$coupons = class_exists('YRR_Coupons_Model') ? YRR_Coupons_Model::get_all() : [];
function yrr_coupon_status_badge($c) {
    if (!$c->is_active) return ['#6c757d', 'Inactive'];
    $now = current_time('mysql');
    if (!empty($c->valid_to) && $c->valid_to < $now) return ['#dc3545', 'Expired'];
    if ($c->usage_limit > 0 && $c->usage_count >= $c->usage_limit) return ['#ffc107', 'Used Up'];
    return ['#28a745', 'Active'];
}
?>
<div class="wrap">
  <div style="max-width:1440px;margin:24px auto 24px auto;background:white;padding:21px 0 21px 0;border-radius:18px;box-shadow:0 7px 22px rgba(0,0,0,0.09);">
    <div style="text-align:center;margin-bottom:12px;padding-bottom:16px;border-bottom:3px solid #ffc107;">
      <h1 style="font-size:2rem;color:#2c3e50;margin:0;">🎟️ Discount Coupons & Promotions</h1>
      <p style="color:#6c757d;margin:4px 0 0;font-size:1.08rem;">Create, edit, and manage all your coupons. Big, bold, and beautifully responsive—6 per row!</p>
    </div>
    <!-- Add Coupon Form -->
    <div style="background:#fffbea;padding:18px 22px 13px 22px;border-radius:13px;margin:10px 40px 22px 40px;border:2px solid #ffc107;">
      <form method="post">
        <?php wp_nonce_field('yrr_coupon_action','coupon_nonce'); ?>
        <input type="hidden" name="add_coupon" value="1">
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;">
          <input type="text" name="code" placeholder="Code*" maxlength="20" required style="padding:14px 12px;font-size:1.14em;border-radius:9px;border:1.5px solid #e9ecef;">
          <select name="discount_type" style="padding:14px 12px;font-size:1.13em;border-radius:9px;border:1.5px solid #e9ecef;">
            <option value="percentage">Percent (%)</option>
            <option value="fixed">Fixed Amount</option>
          </select>
          <input type="number" step="0.01" name="discount_value" min="0.01" placeholder="Value*" required style="padding:14px 12px;font-size:1.15em;border-radius:9px;border:1.5px solid #e9ecef;">
          <input type="number" step="0.01" name="minimum_amount" min="0" placeholder="Min Order" style="padding:14px 12px;font-size:1.11em;border-radius:9px;border:1.5px solid #e9ecef;">
          <input type="number" name="usage_limit" min="0" placeholder="Limit (0=∞)" style="padding:14px 12px;font-size:1.10em;border-radius:9px;border:1.5px solid #e9ecef;">
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:13px;margin-top:13px;">
          <input type="date" name="valid_from" style="padding:12px 13px;font-size:1.09em;border-radius:9px;border:1.5px solid #e9ecef;">
          <input type="date" name="valid_to" style="padding:12px 13px;font-size:1.09em;border-radius:9px;border:1.5px solid #e9ecef;">
          <label style="font-size:1em;color:#248d6a;display:flex;align-items:center;gap:10px;">
            <input type="checkbox" name="is_active" value="1" checked style="margin:0 7px 0 0;transform:scale(1.24);"> Active
          </label>
        </div>
        <div style="text-align:right;margin-top:15px;">
          <button type="submit" class="button button-primary" style="padding:13px 40px;font-size:1.18em;border-radius:8px;font-weight:600;">➕ Add Coupon</button>
        </div>
      </form>
    </div>
    <!-- Coupons Grid: 6 per row, big cards -->
    <div style="display:grid;grid-template-columns:repeat(6,minmax(185px,1fr));gap:22px;">
      <?php foreach ($coupons as $c): list($badge,$label)=yrr_coupon_status_badge($c); ?>
      <div style="background:white;border:2px solid #ffc107;border-radius:15px;padding:20px 14px 16px 14px;position:relative;min-height:115px;box-sizing:border-box;">
        <div style="text-align:center;margin-bottom:8px;">
          <span style="display:inline-block;font-size:1.32rem;font-weight:900;color:#ffc107;"><?php echo esc_html($c->code); ?></span>
        </div>
        <div style="text-align:center;font-size:1.1rem;margin-bottom:4px;">
          <?php echo esc_html($c->discount_type==='fixed' ? '$'.number_format($c->discount_value,2) : $c->discount_value.'%'); ?>
        </div>
        <div style="text-align:center;font-size:0.97rem;">
          <?php echo $c->usage_limit > 0 ? "Used: $c->usage_count/$c->usage_limit" : "Unlimited"; ?>
        </div>
        <div style="text-align:center;font-size:0.97rem;">
          <?php echo !empty($c->valid_to) ? "Expiry: ".date('Y-m-d',strtotime($c->valid_to)) : "No expiry"; ?>
        </div>
        <div style="margin:5px 0 0;text-align:center;">
          <span style="background:<?php echo $badge;?>;color:#fff;padding:5px 14px;border-radius:12px;font-size:0.99rem;font-weight:700;"><?php echo $label; ?></span>
        </div>
        <div style="margin-top:11px;text-align:center;">
          <button onclick="editCoupon(<?php echo htmlspecialchars(json_encode($c)); ?>)" type="button"
            style="background:#ffc107;color:#333;border:none;padding:6px 14px;border-radius:8px;font-size:1em;font-weight:700;cursor:pointer;">✏️ Edit</button>
          <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=yrr-coupons&delete_coupon='.$c->id), 'yrr_coupon_action'); ?>"
             onclick="return confirm('Delete this coupon? This cannot be undone.')" 
             style="background:#dc3545;color:white;padding:6px 14px;text-decoration:none;border-radius:8px;font-size:1em;font-weight:700;">
            🗑️ Delete
          </a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($coupons)): ?>
        <div style="padding:35px 15px;color:#6c757d;text-align:center;">No coupons yet. Add your first above.</div>
      <?php endif;?>
    </div>
  </div>
</div>
<!-- Edit Coupon Modal -->
<div id="editCouponModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:10000;align-items:center;justify-content:center;">
  <div style="background:white;padding:23px 15px;border-radius:16px;width:95%;max-width:370px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:13px;">
      <h3 style="margin:0;font-size:1.09em;">✏️ Edit Coupon</h3>
      <button onclick="closeCouponModal()" style="background:none;border:none;font-size:17px;color:#6c757d;cursor:pointer;">×</button>
    </div>
    <form method="post">
      <?php wp_nonce_field('yrr_coupon_action','coupon_nonce'); ?>
      <input type="hidden" id="edit_coupon_id" name="coupon_id">
      <input type="hidden" name="update_coupon" value="1">
      <input type="text" id="edit_code" name="code" required style="width:97%;padding:9px;margin-bottom:8px;font-size:1.04em;" maxlength="20">
      <select id="edit_discount_type" name="discount_type" style="width:99%;padding:9px;font-size:1.01em;">
        <option value="percentage">Percentage</option>
        <option value="fixed">Fixed Amount</option>
      </select>
      <input type="number" step="0.01" id="edit_discount_value" name="discount_value" min="0.01" required style="width:97%;padding:9px;margin-bottom:8px;">
      <input type="number" step="0.01" id="edit_minimum_amount" name="minimum_amount" min="0" style="width:97%;padding:9px;margin-bottom:8px;">
      <input type="number" id="edit_usage_limit" name="usage_limit" min="0" style="width:97%;padding:9px;margin-bottom:8px;">
      <input type="date" id="edit_valid_from" name="valid_from" style="width:97%;padding:9px;margin-bottom:8px;">
      <input type="date" id="edit_valid_to" name="valid_to" style="width:97%;padding:9px;margin-bottom:8px;">
      <label style="font-size:0.97em;color:#248d6a;display:flex;align-items:center;gap:8px;">
        <input type="checkbox" id="edit_is_active" name="is_active" value="1" style="transform:scale(1.15);margin:0 8px 0 0;">
        Active
      </label>
      <div style="margin-top:13px;text-align:right;">
        <button type="button" onclick="closeCouponModal()" style="background:#6c757d;color:white;border:none;padding:7px 15px;border-radius:9px;margin-right:10px;">Cancel</button>
        <button type="submit" style="background:linear-gradient(135deg,#ffc107 0%,#17a2b8 100%);color:#222;border:none;padding:7px 15px;border-radius:9px;font-weight:700;">💾 Save</button>
      </div>
    </form>
  </div>
</div>
<script>
function editCoupon(c){
    document.getElementById('edit_coupon_id').value = c.id || '';
    document.getElementById('edit_code').value = c.code || '';
    document.getElementById('edit_discount_type').value = c.discount_type || 'percentage';
    document.getElementById('edit_discount_value').value = c.discount_value || '';
    document.getElementById('edit_minimum_amount').value = c.minimum_amount || '';
    document.getElementById('edit_usage_limit').value = c.usage_limit || 0;
    document.getElementById('edit_valid_from').value = c.valid_from ? c.valid_from.substring(0,10):'';
    document.getElementById('edit_valid_to').value = c.valid_to ? c.valid_to.substring(0,10):'';
    document.getElementById('edit_is_active').checked = c.is_active == 1;
    document.getElementById('editCouponModal').style.display = 'flex';
}
function closeCouponModal(){ document.getElementById('editCouponModal').style.display='none'; }
document.getElementById('editCouponModal').addEventListener('click', function(e) { if (e.target === this) closeCouponModal(); });
</script>
<style>
@media (max-width:1400px){
  div[style*="grid-template-columns:repeat(6"] {grid-template-columns:repeat(3,minmax(210px,1fr)) !important;}
}
@media (max-width:900px){
  div[style*="grid-template-columns:repeat(6"] {grid-template-columns:repeat(2,minmax(200px,1fr)) !important;}
}
@media (max-width:600px){
  div[style*="grid-template-columns:repeat(6"] {grid-template-columns:repeat(1,minmax(210px,1fr)) !important;}
}
</style>
