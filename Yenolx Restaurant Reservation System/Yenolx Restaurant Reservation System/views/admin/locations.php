
<?php 
<a href="../../reset-password.php" class="btn btn-outline-secondary">Change Password</a>

if (!defined('ABSPATH')) exit;

// ADD location
if (!empty($_POST['add_location']) && wp_verify_nonce($_POST['location_nonce'], 'yrr_location_action')) {
    $data = [
        'name'  => sanitize_text_field($_POST['location_name']),
        'label' => sanitize_text_field($_POST['label']),
        'notes' => sanitize_textarea_field($_POST['notes'])
    ];
    if (class_exists('YRR_Locations_Model')) YRR_Locations_Model::create($data);
    wp_redirect(admin_url('admin.php?page=yrr-locations&message=loc_added')); exit;
}
// EDIT location
if (!empty($_POST['update_location']) && wp_verify_nonce($_POST['location_nonce'], 'yrr_location_action')) {
    $id = intval($_POST['location_id']);
    $data = [
        'name'  => sanitize_text_field($_POST['location_name']),
        'label' => sanitize_text_field($_POST['label']),
        'notes' => sanitize_textarea_field($_POST['notes'])
    ];
    if ($id && class_exists('YRR_Locations_Model')) YRR_Locations_Model::update($id, $data);
    wp_redirect(admin_url('admin.php?page=yrr-locations&message=loc_updated')); exit;
}
// DELETE
if (isset($_GET['delete_location']) && check_admin_referer('yrr_location_action')) {
    $id = intval($_GET['delete_location']);
    if ($id && class_exists('YRR_Locations_Model')) YRR_Locations_Model::delete($id);
    wp_redirect(admin_url('admin.php?page=yrr-locations&message=loc_deleted')); exit;
}
$locations = class_exists('YRR_Locations_Model') ? YRR_Locations_Model::get_all() : [];
?>
<div class="wrap">
  <div style="max-width:1180px;margin:22px auto 25px auto;background:white;padding:13px 0 16px 0;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,0.08);">
    <div style="text-align:center;margin-bottom:6px;padding-bottom:10px;border-bottom:2px solid #ffc107;">
      <h1 style="font-size:1.53rem;color:#2c3e50;padding:0 0 2px 0;margin:0;">🏢 Locations &amp; Dining Areas</h1>
      <p style="color:#6c757d;margin:2px 0 0;font-size:0.92rem;">Manage all dining areas, patios, zones for table assignments</p>
    </div>
    <!-- Add Location Form -->
    <div style="background:#fffbea;padding:13px 18px 7px 18px;border-radius:10px;margin:9px 22px 18px 22px;border:2px solid #ffc107;">
      <form method="post">
        <?php wp_nonce_field('yrr_location_action','location_nonce'); ?>
        <input type="hidden" name="add_location" value="1">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
          <input type="text" name="location_name" required maxlength="30" placeholder="Location Name (e.g., Patio)" style="padding:8px 7px;border-radius:7px;border:1.5px solid #e9ecef;">
          <input type="text" name="label" maxlength="15" placeholder="Short Label (optional)" style="padding:8px 7px;border-radius:7px;border:1.5px solid #e9ecef;">
          <input type="text" name="notes" maxlength="32" placeholder="Notes (e.g., Smoking)" style="padding:8px 7px;border-radius:7px;border:1.5px solid #e9ecef;">
        </div>
        <div style="text-align:right;margin-top:7px;">
          <button type="submit" class="button button-primary" style="padding:7px 23px;font-size:1.01em;border-radius:6px;">➕ Add Location</button>
        </div>
      </form>
    </div>
    <!-- Locations Card Grid (8 per row) -->
    <div style="display:grid;grid-template-columns:repeat(8,minmax(104px,1fr));gap:8px;">
      <?php foreach ($locations as $loc): ?>
      <div style="background:#fff;border:2px solid #ffc107;border-radius:13px;padding:11px 3px 10px 6px;position:relative;min-height:88px;box-sizing:border-box;">
        <div style="text-align:center;margin-bottom:2px;">
          <span style="display:inline-block;font-size:1.19rem;font-weight:900;color:#e8a800;"><?php echo esc_html($loc->name); ?></span>
        <?php if (!empty($loc->label)): ?>
          <span style="display:inline-block;font-size:0.92em;background:#f3e542;color:#877200;padding:2px 9px;border-radius:9px;margin-left:5px;"><?php echo esc_html($loc->label); ?></span>
        <?php endif;?>
        </div>
        <div style="font-size:0.95em;text-align:center;color:#363e52;margin-bottom:3px;word-break:break-all;"><?php echo esc_html($loc->notes); ?></div>
        <div style="margin-top:4px;text-align:center;">
          <button onclick="editLoc(<?php echo htmlspecialchars(json_encode($loc)); ?>)" type="button"
            style="background:#ffc107;color:#333;border:none;padding:3px 8px;border-radius:7px;font-size:0.96em;font-weight:700;cursor:pointer;">✏️ Edit</button>
          <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=yrr-locations&delete_location='.$loc->id), 'yrr_location_action'); ?>"
             onclick="return confirm('Delete this location? This cannot be undone.')" 
             style="background:#dc3545;color:white;padding:3px 8px;text-decoration:none;border-radius:7px;font-size:0.96em;font-weight:700;">
            🗑️ Delete
          </a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($locations)): ?>
        <div style="padding:17px 7px;color:#6c757d;text-align:center;">No locations yet. Add your first above.</div>
      <?php endif;?>
    </div>
  </div>
</div>
<!-- Edit Locations Modal -->
<div id="editLocModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:10000;align-items:center;justify-content:center;">
  <div style="background:white;padding:17px 13px;border-radius:13px;width:95%;max-width:310px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:9px;">
      <h3 style="margin:0;font-size:1.14em;">✏️ Edit Location</h3>
      <button onclick="closeLocModal()" style="background:none;border:none;font-size:16px;color:#6c757d;cursor:pointer;">×</button>
    </div>
    <form method="post">
      <?php wp_nonce_field('yrr_location_action','location_nonce'); ?>
      <input type="hidden" id="edit_location_id" name="location_id">
      <input type="hidden" name="update_location" value="1">
      <label>Name*<input type="text" id="edit_location_name" name="location_name" required style="width:97%;padding:6px;margin-bottom:5px;"></label>
      <label>Label<input type="text" id="edit_label" name="label" style="width:97%;padding:6px;margin-bottom:5px;"></label>
      <label>Notes<input type="text" id="edit_notes" name="notes" style="width:97%;padding:6px;margin-bottom:8px;"></label>
      <div style="margin-top:8px;text-align:right;">
        <button type="button" onclick="closeLocModal()" style="background:#6c757d;color:white;border:none;padding:7px 13px;border-radius:8px;margin-right:9px;">Cancel</button>
        <button type="submit" style="background:linear-gradient(135deg,#ffc107 0%,#f3c200 100%);color:#222;border:none;padding:7px 13px;border-radius:8px;font-weight:700;">💾 Save</button>
      </div>
    </form>
  </div>
</div>
<script>
function editLoc(loc){
    document.getElementById('edit_location_id').value = loc.id || '';
    document.getElementById('edit_location_name').value = loc.name || '';
    document.getElementById('edit_label').value = loc.label || '';
    document.getElementById('edit_notes').value = loc.notes || '';
    document.getElementById('editLocModal').style.display = 'flex';
}
function closeLocModal(){ document.getElementById('editLocModal').style.display='none'; }
document.getElementById('editLocModal').addEventListener('click', function(e) { if (e.target === this) closeLocModal(); });
</script>
<style>
@media (max-width:900px){div[style*="grid-template-columns:repeat(8,minmax(104px,1fr))"] {grid-template-columns:repeat(4,minmax(120px,1fr))!important;}}
@media (max-width:600px){div[style*="grid-template-columns:repeat(8,minmax(104px,1fr))"] {grid-template-columns:repeat(2,minmax(140px,1fr))!important;}}
</style>
