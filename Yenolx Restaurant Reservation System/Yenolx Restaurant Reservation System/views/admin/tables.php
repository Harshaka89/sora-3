<?php
<?php include_once('../../includes/auth-check.php'); ?>

if (!defined('ABSPATH')) exit;

// Handle ADD
if (!empty($_POST['add_table']) && wp_verify_nonce($_POST['table_nonce'], 'yrr_table_action')) {
    $data = [
        'table_number' => sanitize_text_field($_POST['table_number']),
        'capacity'     => intval($_POST['capacity']),
        'location'     => sanitize_text_field($_POST['location']),
        'table_type'   => sanitize_text_field($_POST['table_type']),
        'status'       => 'available',
    ];
    if (class_exists('YRR_Tables_Model')) YRR_Tables_Model::create($data);
    wp_redirect(admin_url('admin.php?page=yrr-tables&message=table_added')); exit;
}
// Handle edit/save (modal)
if (!empty($_POST['update_table']) && wp_verify_nonce($_POST['table_nonce'], 'yrr_table_action')) {
    $id = intval($_POST['table_id']);
    $data = [
        'table_number' => sanitize_text_field($_POST['table_number']),
        'capacity'     => intval($_POST['capacity']),
        'location'     => sanitize_text_field($_POST['location']),
        'table_type'   => sanitize_text_field($_POST['table_type']),
    ];
    if ($id && class_exists('YRR_Tables_Model')) YRR_Tables_Model::update($id, $data);
    wp_redirect(admin_url('admin.php?page=yrr-tables&message=table_updated')); exit;
}
// Handle delete
if (isset($_GET['delete_table']) && check_admin_referer('yrr_table_action')) {
    $id = intval($_GET['delete_table']);
    if ($id && class_exists('YRR_Tables_Model')) YRR_Tables_Model::delete($id);
    wp_redirect(admin_url('admin.php?page=yrr-tables&message=table_deleted')); exit;
}
// AJAX for status cycle
if (isset($_POST['yrr_table_status_change']) && check_ajax_referer('yrr_table_ajax', 'nonce', false)) {
    $table_id = intval($_POST['table_id']);
    $status = sanitize_text_field($_POST['status']);
    if ($table_id && in_array($status, ['available','booked','maintenance'], true) && class_exists('YRR_Tables_Model')) {
        YRR_Tables_Model::update($table_id, ['status'=>$status]);
        wp_send_json_success(['status'=>$status]);
    }
    wp_send_json_error(); exit;
}
wp_enqueue_script('jquery');
$tables = class_exists('YRR_Tables_Model') ? YRR_Tables_Model::get_all() : [];
function yrr_table_status_badge($status) {
    if ($status === 'maintenance')  return ['#dc3545', '#fff', '🛠'];
    if ($status === 'booked')       return ['#ffc107', '#333', '📙'];
    return ['#28a745', '#fff', '🟢'];
}
?>
<div class="wrap">
  <div style="max-width:1180px;margin:22px auto 25px auto;background:white;padding:13px 0 16px 0;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,0.09);">
    <div style="text-align:center;margin-bottom:6px;padding-bottom:10px;border-bottom:2px solid #28a745;">
      <h1 style="font-size:1.5rem;color:#2c3e50;padding-bottom:2px;margin:0;">🍽️ Tables Management</h1>
      <p style="color:#6c757d;margin:2px 0 0;font-size:0.89rem;">Click badge: <span style="color:#28a745;">Available</span> → <span style="color:#a88a04;">Booked</span> → <span style="color:#dc3545;">Maintenance</span></p>
    </div>
    <!-- Add Table Form -->
    <div style="background:#e8f5e8;padding:13px 20px 7px 20px;border-radius:10px;margin:10px 22px 18px 22px;border:2px solid #28a745;">
      <form method="post">
        <?php wp_nonce_field('yrr_table_action','table_nonce'); ?>
        <input type="hidden" name="add_table" value="1">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
          <input type="text" name="table_number" required maxlength="20" placeholder="Table Number" style="padding:8px 7px;border-radius:7px;border:1.5px solid #e9ecef;">
          <input type="number" name="capacity" min="1" max="20" required value="4" placeholder="Capacity" style="padding:8px 7px;border-radius:7px;border:1.5px solid #e9ecef;">
          <select name="location" style="padding:8px 7px;border-radius:7px;border:1.5px solid #e9ecef;">
            <option value="Center">Center</option><option value="Window">Window</option><option value="Private">Private</option>
            <option value="VIP">VIP</option><option value="Outdoor">Outdoor</option><option value="Bar">Bar</option>
          </select>
          <select name="table_type" style="padding:8px 7px;border-radius:7px;border:1.5px solid #e9ecef;">
            <option value="standard">Standard</option><option value="booth">Booth</option><option value="high_top">High Top</option>
            <option value="round">Round</option><option value="square">Square</option><option value="rectangular">Rectangular</option>
          </select>
        </div>
        <div style="text-align:right;margin-top:7px;">
          <button type="submit" class="button button-primary" style="padding:7px 26px;font-size:1.03em;border-radius:6px;">🍽️ Add Table</button>
        </div>
      </form>
    </div>
    <!-- Table Card Grid 8 per row -->
    <div style="display:grid;grid-template-columns:repeat(8,minmax(96px,1fr));gap:7px;">
      <?php foreach ($tables as $table): 
        $status = strtolower($table->status ?? 'available');
        list($bg, $txt, $icon) = yrr_table_status_badge($status); ?>
      <div style="background:#fff;border:2px solid <?php echo $bg;?>;border-radius:11px;padding:6px 4px 7px 4px;position:relative;min-height:95px;box-sizing:border-box;">
        <div class="table-status-badge"
             data-table="<?php echo esc_attr($table->id); ?>"
             data-status="<?php echo esc_attr($status); ?>"
             title="Click to change status"
             style="position:absolute;top:-7px;right:7px;background:<?php echo $bg;?>;color:<?php echo $txt;?>;cursor:pointer;padding:4px 9px;border-radius:12px;font-size:1.19em;font-weight:700;box-shadow:0 2px 7px rgba(0,0,0,0.09);">
            <?php echo $icon; ?>
        </div>
        <div style="text-align:center;">
          <span style="display:inline-block;font-size:1.35rem;font-weight:900;color:#222;margin-bottom:2px;"><?php echo esc_html($table->table_number); ?></span>
        </div>
        <div style="text-align:center;font-size:1.45em;line-height:1;margin-bottom:1px;">🍽️</div>
        <div style="font-size:0.98em;text-align:center;color:#363e52;margin-bottom:1px;">👥 <span style="font-weight:600;"><?php echo intval($table->capacity); ?></span></div>
        <div style="font-size:0.77em;text-align:center;color:#6c757d;"><?php echo esc_html($table->location); ?></div>
        <div style="margin-top:4px;text-align:center;">
          <button onclick="editTable(<?php echo htmlspecialchars(json_encode($table)); ?>)" type="button"
            style="background:#17a2b8;color:white;border:none;padding:4px 10px 4px 10px;border-radius:6px;font-size:0.87em;font-weight:700;cursor:pointer;">
            ✏️ Edit
          </button>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($tables)): ?>
        <div style="padding:19px 7px;color:#6c757d;text-align:center;">No tables found.</div>
      <?php endif;?>
    </div>
  </div>
</div>
<!-- Edit Table Modal (as before) -->
<div id="editTableModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.65);z-index:10000;align-items:center;justify-content:center;">
  <div style="background:white;padding:22px;border-radius:14px;width:95%;max-width:390px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
      <h3 style="margin:0;font-size:1.05em;">✏️ Edit Table</h3>
      <button onclick="closeTableModal()" style="background:none;border:none;font-size:17px;color:#6c757d;cursor:pointer;">×</button>
    </div>
    <form method="post">
      <?php wp_nonce_field('yrr_table_action','table_nonce'); ?>
      <input type="hidden" id="edit_table_id" name="table_id">
      <input type="hidden" name="update_table" value="1">
      <label>Table Number*<input type="text" id="edit_table_number" name="table_number" required style="width:97%;padding:6px;margin-bottom:6px;"></label>
      <label>Capacity*<input type="number" id="edit_capacity" name="capacity" min="1" max="20" required style="width:97%;padding:6px;text-align:center;"></label>
      <label>Location
        <select id="edit_location" name="location" style="width:99%;padding:6px;">
          <option value="Center">Center Area</option><option value="Window">Window Side</option>
          <option value="Private">Private Section</option><option value="VIP">VIP Area</option>
          <option value="Outdoor">Outdoor Seating</option><option value="Bar">Bar Area</option>
        </select>
      </label>
      <label>Table Type
        <select id="edit_table_type" name="table_type" style="width:99%;padding:6px;">
          <option value="standard">Standard Table</option><option value="booth">Booth Seating</option>
          <option value="high_top">High Top Table</option><option value="round">Round Table</option>
          <option value="square">Square Table</option><option value="rectangular">Rectangular Table</option>
        </select>
      </label>
      <div style="margin-top:10px;text-align:right;">
        <button type="button" onclick="closeTableModal()" style="background:#6c757d;color:white;border:none;padding:6px 13px;border-radius:8px;margin-right:9px;">Cancel</button>
        <button type="submit" style="background:linear-gradient(135deg,#28a745 0%,#20c997 100%);color:white;border:none;padding:6px 13px;border-radius:8px;font-weight:600;">💾 Save</button>
      </div>
    </form>
  </div>
</div>
<script>
jQuery(function($){
    function nextStatus(current) {
        if (current==='available') return 'booked';
        if (current==='booked') return 'maintenance';
        return 'available';
    }
    var badgeMap = {
      available:{color:'#28a745',text:'🟢'},booked:{color:'#ffc107',text:'📙'},maintenance:{color:'#dc3545',text:'🛠'}
    };
    $('.table-status-badge').on('click', function(){
        var $badge = $(this),
          tid = $badge.data('table'),
          st = $badge.data('status'),
          ns = nextStatus(st);
        $.post(ajaxurl, {
            action:'yrr_table_status_change',
            yrr_table_status_change:1,
            table_id:tid,
            status:ns,
            nonce:'<?php echo wp_create_nonce("yrr_table_ajax"); ?>'
        }, function(resp){
            if(resp && resp.success) {
                $badge.data('status', ns)
                      .html(badgeMap[ns].text)
                      .css('background',badgeMap[ns].color)
                      .css('color',ns==='booked'?'#333':'#fff');
                $badge.closest('div').css('border-color',badgeMap[ns].color);
            } else alert('Update failed!');
        });
    });
    // Edit modal logic
    window.editTable = function(table){
        $('#edit_table_id').val(table.id||'');
        $('#edit_table_number').val(table.table_number||'');
        $('#edit_capacity').val(table.capacity||4);
        $('#edit_location').val(table.location||'Center');
        $('#edit_table_type').val(table.table_type||'standard');
        $('#editTableModal').css('display','flex');
    }
    window.closeTableModal = function(){
        $('#editTableModal').hide();
    }
    $('#editTableModal').on('click',function(e){if(e.target===this)closeTableModal();});
});
</script>
<style>
@media (max-width:900px){div[style*="grid-template-columns:repeat(8,minmax(104px,1fr))"] {grid-template-columns:repeat(4,minmax(120px,1fr))!important;}}
@media (max-width:600px){div[style*="grid-template-columns:repeat(8,minmax(104px,1fr))"] {grid-template-columns:repeat(2,minmax(150px,1fr))!important;}}
.table-status-badge:hover{filter:brightness(1.13);transform:scale(1.19);}
</style>
