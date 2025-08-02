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
