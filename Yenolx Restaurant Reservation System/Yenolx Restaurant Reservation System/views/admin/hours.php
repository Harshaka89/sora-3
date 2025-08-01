<?php
if (!defined('ABSPATH')) exit;

// Helper for safely accessing hour object properties
function yrr_get_property_hours($object, $property, $default = '') {
    return (property_exists($object, $property) && !empty($object->$property)) ? $object->$property : $default;
}

$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$day_labels = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

// Handle save
$success_msg = '';
if (!empty($_POST['hours_nonce']) && wp_verify_nonce($_POST['hours_nonce'], 'yrr_hours_save')) {
    $saved_count = 0;
    foreach ($days as $day) {
        $is_closed = !empty($_POST["{$day}_closed"]) ? 1 : 0;
        $open = !$is_closed ? sanitize_text_field($_POST["{$day}_open"]) : '';
        $close = !$is_closed ? sanitize_text_field($_POST["{$day}_close"]) : '';
        if (class_exists('YRR_Hours_Model')) {
            YRR_Hours_Model::set_hours_for_day($day, $open, $close, $is_closed);
            $saved_count++;
        }
    }
    $query = add_query_arg(['page'=>'yrr-hours','message'=>'hours_saved','count'=>$saved_count],admin_url('admin.php'));
    wp_redirect($query); exit;
}

// Load current hours
$hours = [];
if (class_exists('YRR_Hours_Model')) {
    foreach ($days as $d) {
        $hours[$d] = ['all_day'=>(object)YRR_Hours_Model::get_hours_for_day(ucfirst($d))];
    }
}
?>
<div class="wrap">
    <div style="max-width:1200px;margin:20px auto;background:white;padding:30px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.08);">
        <!-- Header & Feedback -->
        <div style="text-align:center;margin-bottom:30px;padding-bottom:20px;border-bottom:3px solid #ffc107;">
            <h1 style="font-size:2.5rem;color:#2c3e50;margin:0;">⏰ Operating Hours Management</h1>
            <p style="color:#6c757d;margin:10px 0 0;font-size:1.13rem;">Set weekly open/close times, manage closed days ("Amusement" mode)</p>
        </div>
        <?php if (!empty($_GET['message']) && $_GET['message']==='hours_saved'): ?>
            <div style="padding: 15px; margin: 20px 0; border-radius: 8px; background: #d4edda; color: #155724; border: 2px solid #28a745;">
                <h4 style="margin:0">✅ Saved! <?php echo intval($_GET['count']); ?> day(s) updated.</h4>
            </div>
        <?php endif; ?>

        <!-- Week Overview -->
        <div style="background:#e3f2fd;padding:20px;border-radius:10px;margin-bottom:30px;border-left:5px solid #2196f3;">
            <h3 style="margin:0 0 15px 0;color:#1976d2;">📊 Current Weekly Status</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;">
                <?php foreach ($days as $i=>$day):
                    $day_hours = $hours[$day]['all_day']??null; $is_closed = $day_hours?intval(yrr_get_property_hours($day_hours,'is_closed',0)):0;
                    $open_time = $day_hours&&!$is_closed?yrr_get_property_hours($day_hours,'open_time','10:00:00'):null;
                    $close_time = $day_hours&&!$is_closed?yrr_get_property_hours($day_hours,'close_time','22:00:00'):null;
                ?>
                <div style="text-align:center;padding:10px;background:white;border-radius:8px;">
                    <div style="font-weight:bold;margin-bottom:5px;"><?php echo $day_labels[$i]; ?></div>
                    <?php if ($is_closed): ?>
                        <div style="color:#dc3545;font-weight:bold;">🎡 Amusement/Closed</div>
                    <?php else: ?>
                        <div style="color:#28a745;font-size:0.92rem;">
                            🟢 <?php echo date('g:i A',strtotime($open_time)); ?><br>to <?php echo date('g:i A',strtotime($close_time)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach;?>
            </div>
        </div>

        <!-- Form -->
        <form method="post">
            <?php wp_nonce_field('yrr_hours_save','hours_nonce'); ?>
            <div style="background:#fff3cd;padding:30px;border-radius:15px;margin-bottom:30px;border:3px solid #ffc107;">
                <h3 style="margin:0 0 25px 0;color:#856404;">📋 Configure Weekly Operating Hours</h3>
                <div style="display:grid;gap:25px;">
                    <?php foreach ($days as $i=>$day):
                        $day_hours = $hours[$day]['all_day']??null;
                        $is_closed = $day_hours?intval(yrr_get_property_hours($day_hours,'is_closed',0)):0;
                        $open_time = $day_hours?yrr_get_property_hours($day_hours,'open_time','10:00:00'):'10:00:00';
                        $close_time = $day_hours?yrr_get_property_hours($day_hours,'close_time','22:00:00'):'22:00:00';
                        $open_time = substr($open_time,0,5); $close_time = substr($close_time,0,5);?>
                    <div style="background:white;padding:18px;border-radius:12px;border:2px solid #e9ecef;">
                        <div style="display:grid;grid-template-columns:auto 1fr auto auto auto;gap:20px;align-items:center;">
                            <div style="min-width:105px;">
                                <h4 style="margin:0;font-size:1.20rem;color:#2c3e50;"><?php echo $day_labels[$i]; ?></h4>
                            </div>
                            <div style="text-align:center;">
                                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                                    <input type="checkbox" name="<?php echo $day; ?>_closed" value="1" <?php checked($is_closed,1); ?>
                                           onchange="toggleDayHours('<?php echo $day; ?>')" style="transform:scale(1.27);">
                                    <span style="font-weight:bold;color:#dc3545;">🎡 Amusement/Closed</span>
                                </label>
                            </div>
                            <div>
                                <label style="display:block;margin-bottom:5px;font-weight:bold;color:#28a745;">🕐 Opens</label>
                                <input type="time" name="<?php echo $day; ?>_open" value="<?php echo $open_time; ?>" id="<?php echo $day; ?>_open"
                                    <?php echo $is_closed?'disabled':'';?> style="padding:8px;border:2px solid #e9ecef;border-radius:6px;font-size:1rem;<?php echo $is_closed?'background:#f8f9fa;color:#6c757d;':'';?>">
                            </div>
                            <div>
                                <label style="display:block;margin-bottom:5px;font-weight:bold;color:#dc3545;">🕕 Closes</label>
                                <input type="time" name="<?php echo $day; ?>_close" value="<?php echo $close_time; ?>" id="<?php echo $day; ?>_close"
                                    <?php echo $is_closed?'disabled':'';?> style="padding:8px;border:2px solid #e9ecef;border-radius:6px;font-size:1rem;<?php echo $is_closed?'background:#f8f9fa;color:#6c757d;':'';?>">
                            </div>
                            <div style="text-align:center;min-width:80px;">
                                <div id="<?php echo $day; ?>_status" style="padding:8px 12px;border-radius:15px;font-size:0.88rem;font-weight:bold;text-transform:uppercase;<?php echo $is_closed?'background:#f8d7da;color:#721c24;':'background:#d4edda;color:#155724;';?>">
                                    <?php echo $is_closed?'CLOSED':'OPEN'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
                <!-- Quick Action Buttons -->
                <div style="margin-top:30px;padding-top:20px;border-top:2px solid #ffc107;text-align:center;">
                    <h4 style="margin:0 0 15px 0;color:#856404;">⚡ Quick Actions</h4>
                    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                        <button type="button" onclick="setAllHours('10:00','22:00')" style="background:#28a745;color:white;border:none;padding:10px 15px;border-radius:6px;font-size:0.9rem;cursor:pointer;">🏪 Standard Hours (10 AM - 10 PM)</button>
                        <button type="button" onclick="setWeekendHours()" style="background:#007cba;color:white;border:none;padding:10px 15px;border-radius:6px;font-size:0.9rem;cursor:pointer;">🎉 Weekend Extended Hours</button>
                        <button type="button" onclick="closeAllDays()" style="background:#dc3545;color:white;border:none;padding:10px 15px;border-radius:6px;font-size:0.9rem;cursor:pointer;">🎡 Close All Days</button>
                        <button type="button" onclick="openAllDays()" style="background:#ffc107;color:black;border:none;padding:10px 15px;border-radius:6px;font-size:0.9rem;cursor:pointer;">🟢 Open All Days</button>
                    </div>
                </div>

                <!-- Save Button -->
                <div style="text-align:center;margin-top:30px;padding-top:20px;border-top:2px solid #ffc107;">
                    <button type="submit" name="save_hours" value="1"
                        style="background:linear-gradient(135deg,#ffc107 0%,#e0a800 100%);color:white;border:none;padding:20px 50px;border-radius:12px;font-size:1.3rem;font-weight:bold;cursor:pointer;">⏰ Save Operating Hours</button>
                    <p style="margin-top:10px;color:#6c757d;">Changes take effect on the next booking/page reload.</p>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDayHours(day) {
    const checkbox = document.querySelector(`input[name="${day}_closed"]`);
    const openInput = document.getElementById(`${day}_open`);
    const closeInput = document.getElementById(`${day}_close`);
    const statusDiv = document.getElementById(`${day}_status`);
    if (checkbox.checked) {
        openInput.disabled = true; closeInput.disabled = true;
        openInput.style.background = '#f8f9fa'; openInput.style.color = '#6c757d';
        closeInput.style.background = '#f8f9fa'; closeInput.style.color = '#6c757d';
        statusDiv.textContent = 'CLOSED'; statusDiv.style.background = '#f8d7da'; statusDiv.style.color = '#721c24';
    } else {
        openInput.disabled = false; closeInput.disabled = false;
        openInput.style.background = 'white'; openInput.style.color = 'black';
        closeInput.style.background = 'white'; closeInput.style.color = 'black';
        statusDiv.textContent = 'OPEN'; statusDiv.style.background = '#d4edda'; statusDiv.style.color = '#155724';
    }
}
function setAllHours(openTime, closeTime) {
    const days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    days.forEach(day=>{
        const cb = document.querySelector(`input[name="${day}_closed"]`);
        if (!cb.checked) {
            document.getElementById(`${day}_open`).value = openTime;
            document.getElementById(`${day}_close`).value = closeTime;
        }
    });
}
function setWeekendHours() {
    ['friday','saturday'].forEach(day=>{
        const cb = document.querySelector(`input[name="${day}_closed"]`);
        if (!cb.checked) {
            document.getElementById(`${day}_open`).value = '11:00';
            document.getElementById(`${day}_close`).value = '23:00';
        }
    });
}
function closeAllDays() {
    ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'].forEach(day=>{
        const cb = document.querySelector(`input[name="${day}_closed"]`);
        cb.checked = true; toggleDayHours(day);
    });
}
function openAllDays() {
    ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'].forEach(day=>{
        const cb = document.querySelector(`input[name="${day}_closed"]`);
        cb.checked = false; toggleDayHours(day);
    });
}
</script>
<style>
@media (max-width:900px){div[style*="grid-template-columns: auto 1fr auto auto auto"]{grid-template-columns:1fr!important;gap:9px!important;}}
button:hover{transform:translateY(-2px);box-shadow:0 4px 8px rgba(0,0,0,0.13);}
input[type="time"]:focus{border-color:#007cba;box-shadow:0 0 0 2px rgba(0,123,186,.24);}
</style>
