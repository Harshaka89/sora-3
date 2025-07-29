<?php
/**
 * New Reservation Modal - Admin interface for creating reservations
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Get available tables for the dropdown
$tables = YRR_Tables_Model::get_all();
$locations = YRR_Locations_Model::get_all(true);
?>

<!-- New Reservation Modal -->
<div id="yrr-new-reservation-modal" class="yrr-modal" style="display: none;">
    <div class="yrr-modal-overlay"></div>
    <div class="yrr-modal-content">
        <div class="yrr-modal-header">
            <h2><?php _e('Add New Reservation', 'yrr'); ?></h2>
            <button type="button" class="yrr-modal-close">&times;</button>
        </div>
        
        <div class="yrr-modal-body">
            <form id="yrr-admin-reservation-form" method="post">
                <?php wp_nonce_field('yrr_admin_action', 'yrr_nonce'); ?>
                <input type="hidden" name="yrr_action" value="create_reservation">
                
                <!-- Customer Information -->
                <div class="yrr-form-section">
                    <h3><?php _e('Customer Information', 'yrr'); ?></h3>
                    
                    <div class="yrr-form-row">
                        <div class="yrr-form-field">
                            <label for="modal_customer_name"><?php _e('Customer Name', 'yrr'); ?> *</label>
                            <input type="text" id="modal_customer_name" name="customer_name" required class="yrr-form-control">
                        </div>
                        
                        <div class="yrr-form-field">
                            <label for="modal_customer_email"><?php _e('Email Address', 'yrr'); ?> *</label>
                            <input type="email" id="modal_customer_email" name="customer_email" required class="yrr-form-control">
                        </div>
                    </div>
                    
                    <div class="yrr-form-field">
                        <label for="modal_customer_phone"><?php _e('Phone Number', 'yrr'); ?> *</label>
                        <input type="tel" id="modal_customer_phone" name="customer_phone" required class="yrr-form-control">
                    </div>
                </div>
                
                <!-- Reservation Details -->
                <div class="yrr-form-section">
                    <h3><?php _e('Reservation Details', 'yrr'); ?></h3>
                    
                    <div class="yrr-form-row">
                        <div class="yrr-form-field">
                            <label for="modal_party_size"><?php _e('Party Size', 'yrr'); ?> *</label>
                            <select id="modal_party_size" name="party_size" required class="yrr-form-control">
                                <option value=""><?php _e('Select party size...', 'yrr'); ?></option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>">
                                        <?php echo $i; ?> <?php echo $i === 1 ? __('guest', 'yrr') : __('guests', 'yrr'); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="yrr-form-field">
                            <label for="modal_reservation_date"><?php _e('Reservation Date', 'yrr'); ?> *</label>
                            <input type="date" id="modal_reservation_date" name="reservation_date" required 
                                   class="yrr-form-control" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="yrr-form-row">
                        <div class="yrr-form-field">
                            <label for="modal_reservation_time"><?php _e('Reservation Time', 'yrr'); ?> *</label>
                            <input type="time" id="modal_reservation_time" name="reservation_time" required class="yrr-form-control">
                        </div>
                        
                        <div class="yrr-form-field">
                            <label for="modal_table_id"><?php _e('Assign Table (Optional)', 'yrr'); ?></label>
                            <select id="modal_table_id" name="table_id" class="yrr-form-control">
                                <option value=""><?php _e('Auto-assign optimal table', 'yrr'); ?></option>
                                <?php if (!empty($tables)): ?>
                                    <?php foreach ($tables as $table): ?>
                                        <option value="<?php echo esc_attr($table->id); ?>">
                                            <?php echo esc_html($table->table_number); ?> 
                                            (<?php printf(__('%d seats', 'yrr'), $table->capacity); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (count($locations) > 1): ?>
                    <div class="yrr-form-field">
                        <label for="modal_location_id"><?php _e('Location', 'yrr'); ?></label>
                        <select id="modal_location_id" name="location_id" class="yrr-form-control">
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo esc_attr($location->id); ?>">
                                    <?php echo esc_html($location->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="location_id" value="1">
                    <?php endif; ?>
                </div>
                
                <!-- Additional Information -->
                <div class="yrr-form-section">
                    <h3><?php _e('Additional Information', 'yrr'); ?></h3>
                    
                    <div class="yrr-form-row">
                        <div class="yrr-form-field">
                            <label for="modal_status"><?php _e('Status', 'yrr'); ?></label>
                            <select id="modal_status" name="status" class="yrr-form-control">
                                <option value="pending"><?php _e('Pending', 'yrr'); ?></option>
                                <option value="confirmed" selected><?php _e('Confirmed', 'yrr'); ?></option>
                                <option value="completed"><?php _e('Completed', 'yrr'); ?></option>
                            </select>
                        </div>
                        
                        <div class="yrr-form-field">
                            <label for="modal_source"><?php _e('Source', 'yrr'); ?></label>
                            <select id="modal_source" name="source" class="yrr-form-control">
                                <option value="admin" selected><?php _e('Admin', 'yrr'); ?></option>
                                <option value="phone"><?php _e('Phone', 'yrr'); ?></option>
                                <option value="walk-in"><?php _e('Walk-in', 'yrr'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="yrr-form-field">
                        <label for="modal_special_requests"><?php _e('Special Requests', 'yrr'); ?></label>
                        <textarea id="modal_special_requests" name="special_requests" rows="3" 
                                class="yrr-form-control" placeholder="<?php _e('Any special requests or notes...', 'yrr'); ?>"></textarea>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="yrr-modal-footer">
            <button type="button" class="button yrr-modal-cancel"><?php _e('Cancel', 'yrr'); ?></button>
            <button type="submit" form="yrr-admin-reservation-form" class="button button-primary">
                <?php _e('Create Reservation', 'yrr'); ?>
            </button>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.yrr-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 100000;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.yrr-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    cursor: pointer;
}

.yrr-modal-content {
    position: relative;
    max-width: 700px;
    margin: 30px auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.yrr-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e1e1e1;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px 8px 0 0;
}

.yrr-modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: white;
}

.yrr-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border-radius: 50%;
    transition: background 0.3s ease;
}

.yrr-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.yrr-modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.yrr-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid #e1e1e1;
    background: #f9f9f9;
    border-radius: 0 0 8px 8px;
}

/* Form Styles */
.yrr-form-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.yrr-form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.yrr-form-section h3 {
    margin: 0 0 15px;
    font-size: 16px;
    color: #2c3e50;
    font-weight: 600;
}

.yrr-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.yrr-form-field {
    display: flex;
    flex-direction: column;
}

.yrr-form-field label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #34495e;
    font-size: 14px;
}

.yrr-form-control {
    padding: 10px 12px;
    border: 2px solid #e1e8ed;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.yrr-form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.yrr-form-control.error {
    border-color: #dc3232;
    box-shadow: 0 0 0 3px rgba(220, 50, 50, 0.1);
}

/* Loading State */
.yrr-modal-loading .yrr-modal-content {
    opacity: 0.7;
    pointer-events: none;
}

.yrr-modal-loading .yrr-modal-footer::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border:
