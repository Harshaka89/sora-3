<?php include_once('../../includes/auth-check.php'); ?>
<link rel="stylesheet" href="../../assets/admin.css">
<script src="../../assets/admin.js"></script>
<a href="../../reset-password.php" class="btn btn-outline-secondary">Change Password</a>

/**
 * All Reservations Admin View - Comprehensive reservation management interface
 * Displays all reservations with advanced filtering, sorting, and bulk actions
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Get controller data
$controller = new YRR_Admin_Controller();
$dashboard_data = $controller->get_dashboard_data();

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;

// Build filter array
$filters = array();
if ($status_filter) $filters['status'] = $status_filter;
if ($date_from) $filters['date_from'] = $date_from;
if ($date_to) $filters['date_to'] = $date_to;
if ($search) $filters['search'] = $search;
if ($location_id) $filters['location_id'] = $location_id;

// Get reservations with filters
$reservations = YRR_Reservation_Model::get_all($filters);
$total_reservations = count($reservations);

// Get statistics for this filtered set
$stats = YRR_Reservation_Model::get_dashboard_stats($location_id);

// Get all locations for filter dropdown
$locations = YRR_Locations_Model::get_all(true);
?>

<div class="yrr-admin-wrap">
    <h1 class="wp-heading-inline">
        <?php _e('All Reservations', 'yrr'); ?>
        <span class="yrr-count">(<?php echo number_format($total_reservations); ?>)</span>
    </h1>
    
    <a href="#" class="page-title-action button button-primary yrr-btn-create-reservation">
    <span class="dashicons dashicons-plus-alt" style="margin-right: 5px;"></span>
    <?php _e('Add New Reservation', 'yrr'); ?>
</a>
    <hr class="wp-header-end">

    <!-- Quick Stats Row -->
    <div class="yrr-quick-stats">
        <div class="yrr-stats-grid">
            <div class="yrr-stat-card yrr-stat-total">
                <div class="yrr-stat-icon">📊</div>
                <div class="yrr-stat-content">
                    <h3><?php echo number_format($stats['total'] ?? 0); ?></h3>
                    <p><?php _e('Total Reservations', 'yrr'); ?></p>
                </div>
            </div>
            
            <div class="yrr-stat-card yrr-stat-revenue">
                <div class="yrr-stat-icon">💰</div>
                <div class="yrr-stat-content">
                    <h3>$<?php echo number_format($stats['revenue'] ?? 0, 2); ?></h3>
                    <p><?php _e('Total Revenue', 'yrr'); ?></p>
                </div>
            </div>
            
            <div class="yrr-stat-card yrr-stat-pending">
                <div class="yrr-stat-icon">⏰</div>
                <div class="yrr-stat-content">
                    <h3><?php echo number_format($stats['pending'] ?? 0); ?></h3>
                    <p><?php _e('Pending Approval', 'yrr'); ?></p>
                </div>
            </div>
            
            <div class="yrr-stat-card yrr-stat-guests">
                <div class="yrr-stat-icon">👥</div>
                <div class="yrr-stat-content">
                    <h3><?php echo number_format($stats['total_guests'] ?? 0); ?></h3>
                    <p><?php _e('Total Guests', 'yrr'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="yrr-filters-section">
        <form method="get" class="yrr-filters-form">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
            
            <div class="yrr-filters-row">
                <div class="yrr-filter-field">
                    <label for="status"><?php _e('Status', 'yrr'); ?></label>
                    <select name="status" id="status" class="yrr-form-control">
                        <option value=""><?php _e('All Statuses', 'yrr'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'yrr'); ?></option>
                        <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php _e('Confirmed', 'yrr'); ?></option>
                        <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completed', 'yrr'); ?></option>
                        <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>><?php _e('Cancelled', 'yrr'); ?></option>
                    </select>
                </div>
                
                <div class="yrr-filter-field">
                    <label for="date_from"><?php _e('From Date', 'yrr'); ?></label>
                    <input type="date" name="date_from" id="date_from" class="yrr-form-control" value="<?php echo esc_attr($date_from); ?>">
                </div>
                
                <div class="yrr-filter-field">
                    <label for="date_to"><?php _e('To Date', 'yrr'); ?></label>
                    <input type="date" name="date_to" id="date_to" class="yrr-form-control" value="<?php echo esc_attr($date_to); ?>">
                </div>
                
                <?php if (count($locations) > 1): ?>
                <div class="yrr-filter-field">
                    <label for="location_id"><?php _e('Location', 'yrr'); ?></label>
                    <select name="location_id" id="location_id" class="yrr-form-control">
                        <option value=""><?php _e('All Locations', 'yrr'); ?></option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo esc_attr($location->id); ?>" <?php selected($location_id, $location->id); ?>>
                                <?php echo esc_html($location->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="yrr-filter-field">
                    <label for="search"><?php _e('Search', 'yrr'); ?></label>
                    <input type="text" name="search" id="search" class="yrr-form-control" 
                           placeholder="<?php _e('Customer name, email, or code...', 'yrr'); ?>" 
                           value="<?php echo esc_attr($search); ?>">
                </div>
            </div>
            
            <div class="yrr-filters-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Filter Reservations', 'yrr'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=yrr-reservations'); ?>" class="button">
                    <?php _e('Clear Filters', 'yrr'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="yrr-bulk-actions">
        <form method="post" id="yrr-bulk-form">
            <?php wp_nonce_field('yrr_bulk_action', 'yrr_bulk_nonce'); ?>
            
            <div class="yrr-bulk-controls">
                <select name="bulk_action" class="yrr-form-control">
                    <option value=""><?php _e('Bulk Actions', 'yrr'); ?></option>
                    <option value="confirm"><?php _e('Confirm Selected', 'yrr'); ?></option>
                    <option value="cancel"><?php _e('Cancel Selected', 'yrr'); ?></option>
                    <option value="complete"><?php _e('Mark as Completed', 'yrr'); ?></option>
                    <option value="delete"><?php _e('Delete Selected', 'yrr'); ?></option>
                </select>
                
                <button type="submit" class="button" disabled id="bulk-action-submit">
                    <?php _e('Apply', 'yrr'); ?>
                </button>
                
                <span class="yrr-selected-count" style="display: none;">
                    <span id="selected-count">0</span> <?php _e('selected', 'yrr'); ?>
                </span>
            </div>

            <!-- Reservations Table -->
            <div class="yrr-reservations-table-container">
                <?php if (empty($reservations)): ?>
                    <div class="yrr-no-reservations">
                        <div class="yrr-empty-state">
                            <div class="yrr-empty-icon">📅</div>
                            <h3><?php _e('No Reservations Found', 'yrr'); ?></h3>
                            <p><?php _e('No reservations match your current filters. Try adjusting your search criteria or create your first reservation.', 'yrr'); ?></p>
                            <a href="#" class="button button-primary yrr-btn-create-reservation">
                                <?php _e('Create First Reservation', 'yrr'); ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <table class="yrr-reservations-table wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input type="checkbox" id="cb-select-all">
                                </td>
                                <th class="manage-column column-code">
                                    <a href="<?php echo esc_url(add_query_arg('orderby', 'reservation_code')); ?>">
                                        <?php _e('Code', 'yrr'); ?>
                                    </a>
                                </th>
                                <th class="manage-column column-customer">
                                    <a href="<?php echo esc_url(add_query_arg('orderby', 'customer_name')); ?>">
                                        <?php _e('Customer', 'yrr'); ?>
                                    </a>
                                </th>
                                <th class="manage-column column-datetime">
                                    <a href="<?php echo esc_url(add_query_arg('orderby', 'reservation_date')); ?>">
                                        <?php _e('Date & Time', 'yrr'); ?>
                                    </a>
                                </th>
                                <th class="manage-column column-party">
                                    <?php _e('Party', 'yrr'); ?>
                                </th>
                                <th class="manage-column column-table">
                                    <?php _e('Table', 'yrr'); ?>
                                </th>
                                <th class="manage-column column-status">
                                    <?php _e('Status', 'yrr'); ?>
                                </th>
                                <th class="manage-column column-revenue">
                                    <?php _e('Revenue', 'yrr'); ?>
                                </th>
                                <th class="manage-column column-source">
                                    <?php _e('Source', 'yrr'); ?>
                                </th>
                                <th class="manage-column column-actions">
                                    <?php _e('Actions', 'yrr'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr class="yrr-reservation-row" data-reservation-id="<?php echo esc_attr($reservation->id); ?>">
                                    <th class="check-column">
                                        <input type="checkbox" name="reservation_ids[]" value="<?php echo esc_attr($reservation->id); ?>" class="yrr-bulk-checkbox">
                                    </th>
                                    
                                    <td class="column-code">
                                        <strong>
                                            <a href="#" class="yrr-reservation-link" data-id="<?php echo esc_attr($reservation->id); ?>">
                                                <?php echo esc_html($reservation->reservation_code); ?>
                                            </a>
                                        </strong>
                                        <div class="yrr-reservation-meta">
                                            <?php _e('Created:', 'yrr'); ?> <?php echo date('M j, Y', strtotime($reservation->created_at)); ?>
                                        </div>
                                    </td>
                                    
                                    <td class="column-customer">
                                        <strong><?php echo esc_html($reservation->customer_name); ?></strong>
                                        <div class="yrr-customer-details">
                                            <div class="yrr-customer-email">
                                                📧 <a href="mailto:<?php echo esc_attr($reservation->customer_email); ?>">
                                                    <?php echo esc_html($reservation->customer_email); ?>
                                                </a>
                                            </div>
                                            <?php if ($reservation->customer_phone): ?>
                                            <div class="yrr-customer-phone">
                                                📞 <a href="tel:<?php echo esc_attr($reservation->customer_phone); ?>">
                                                    <?php echo esc_html($reservation->customer_phone); ?>
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="column-datetime">
                                        <div class="yrr-datetime-display">
                                            <div class="yrr-date">
                                                📅 <?php echo date('D, M j, Y', strtotime($reservation->reservation_date)); ?>
                                            </div>
                                            <div class="yrr-time">
                                                🕐 <?php echo date('g:i A', strtotime($reservation->reservation_time)); ?>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="column-party">
                                        <span class="yrr-party-size">
                                            👥 <?php echo $reservation->party_size; ?> 
                                            <?php echo $reservation->party_size === 1 ? __('guest', 'yrr') : __('guests', 'yrr'); ?>
                                        </span>
                                    </td>
                                    
                                    <td class="column-table">
                                        <?php if ($reservation->table_number): ?>
                                            <span class="yrr-table-assignment">
                                                🪑 <?php echo esc_html($reservation->table_number); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="yrr-no-table"><?php _e('Auto-assign', 'yrr'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="column-status">
                                        <span class="yrr-status-badge yrr-status-<?php echo esc_attr($reservation->status); ?>">
                                            <?php 
                                            $status_icons = array(
                                                'pending' => '⏰',
                                                'confirmed' => '✅',
                                                'completed' => '🎉',
                                                'cancelled' => '❌'
                                            );
                                            echo $status_icons[$reservation->status] ?? '❓';
                                            echo ' ' . ucfirst($reservation->status);
                                            ?>
                                        </span>
                                    </td>
                                    
                                    <td class="column-revenue">
                                        <?php if ($reservation->final_price > 0): ?>
                                            <span class="yrr-revenue-amount">
                                                💰 $<?php echo number_format($reservation->final_price, 2); ?>
                                            </span>
                                            <?php if ($reservation->discount_amount > 0): ?>
                                                <div class="yrr-discount-info">
                                                    💸 -$<?php echo number_format($reservation->discount_amount, 2); ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="yrr-no-revenue">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="column-source">
                                        <span class="yrr-source-badge yrr-source-<?php echo esc_attr($reservation->source ?? 'admin'); ?>">
                                            <?php 
                                            $source_icons = array(
                                                'admin' => '👨‍💼',
                                                'public' => '🌐',
                                                'phone' => '📞',
                                                'walk-in' => '🚶'
                                            );
                                            echo $source_icons[$reservation->source ?? 'admin'] ?? '📝';
                                            echo ' ' . ucfirst($reservation->source ?? 'Admin');
                                            ?>
                                        </span>
                                    </td>
                                    
                                    <td class="column-actions">
                                        <div class="yrr-reservation-actions">
                                            <?php if ($reservation->status === 'pending'): ?>
                                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=yrr-reservations&action=confirm_reservation&id=' . $reservation->id ), 'confirm_reservation_' . $reservation->id ) ); ?>"
                                                   class="button button-small button-primary" title="<?php _e('Confirm Reservation', 'yrr'); ?>">
                                                    ✅ <?php _e('Confirm', 'yrr'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (in_array($reservation->status, ['pending', 'confirmed'])): ?>
                                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=yrr-reservations&action=cancel_reservation&id=' . $reservation->id ), 'cancel_reservation_' . $reservation->id ) ); ?>"
                                                   class="button button-small yrr-btn-cancel" title="<?php _e('Cancel Reservation', 'yrr'); ?>"
                                                   onclick="return confirm('<?php _e('Are you sure you want to cancel this reservation?', 'yrr'); ?>')">
                                                    ❌ <?php _e('Cancel', 'yrr'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($reservation->status === 'confirmed'): ?>
                                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=yrr-reservations&action=complete_reservation&id=' . $reservation->id ), 'complete_reservation_' . $reservation->id ) ); ?>"
                                                   class="button button-small" title="<?php _e('Mark as Completed', 'yrr'); ?>">
                                                    🎉 <?php _e('Complete', 'yrr'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <a href="#" class="button button-small yrr-btn-edit-reservation"
                                               data-id="<?php echo esc_attr($reservation->id); ?>" title="<?php _e('Edit Reservation', 'yrr'); ?>">
                                                ✏️ <?php _e('Edit', 'yrr'); ?>
                                            </a>

                                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=yrr-reservations&action=delete_reservation&id=' . $reservation->id ), 'delete_reservation_' . $reservation->id ) ); ?>"
                                               class="button button-small button-link-delete" title="<?php _e('Delete Reservation', 'yrr'); ?>"
                                               onclick="return confirm('<?php _e('Are you sure you want to delete this reservation? This action cannot be undone.', 'yrr'); ?>')">
                                                🗑️ <?php _e('Delete', 'yrr'); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                
                                <?php if ($reservation->special_requests): ?>
                                <tr class="yrr-special-requests-row" data-reservation-id="<?php echo esc_attr($reservation->id); ?>">
                                    <td colspan="10" class="yrr-special-requests">
                                        <div class="yrr-special-requests-content">
                                            <strong><?php _e('Special Requests:', 'yrr'); ?></strong>
                                            <?php echo esc_html($reservation->special_requests); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Pagination (if needed for large datasets) -->
    <?php if (count($reservations) >= 50): ?>
    <div class="yrr-pagination">
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(__('Displaying %d reservations', 'yrr'), count($reservations)); ?>
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Include the New Reservation Modal -->
<?php include YRR_PLUGIN_PATH . 'views/admin/partials/new-reservation-modal.php'; ?>



<script>
jQuery(document).ready(function($) {
    // Bulk checkbox handling
    $('#cb-select-all').on('change', function() {
        $('.yrr-bulk-checkbox').prop('checked', this.checked);
        updateBulkActions();
    });
    
    $('.yrr-bulk-checkbox').on('change', function() {
        updateBulkActions();
    });
    
    function updateBulkActions() {
        var checkedCount = $('.yrr-bulk-checkbox:checked').length;
        $('#selected-count').text(checkedCount);
        $('.yrr-selected-count').toggle(checkedCount > 0);
        $('#bulk-action-submit').prop('disabled', checkedCount === 0);
        
        // Update select all checkbox
        var totalBoxes = $('.yrr-bulk-checkbox').length;
        $('#cb-select-all').prop('indeterminate', checkedCount > 0 && checkedCount < totalBoxes);
        $('#cb-select-all').prop('checked', checkedCount === totalBoxes);
    }
    
    // Bulk form submission
    $('#yrr-bulk-form').on('submit', function(e) {
        var action = $('select[name="bulk_action"]').val();
        var checkedCount = $('.yrr-bulk-checkbox:checked').length;
        
        if (!action) {
            e.preventDefault();
            alert('Please select a bulk action.');
            return false;
        }
        
        if (checkedCount === 0) {
            e.preventDefault();
            alert('Please select at least one reservation.');
            return false;
        }
        
        if (action === 'delete') {
            if (!confirm('Are you sure you want to delete the selected reservations? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Row highlighting
    $('.yrr-reservations-table tbody tr').on('mouseenter', function() {
        $(this).addClass('yrr-row-highlight');
    }).on('mouseleave', function() {
        $(this).removeClass('yrr-row-highlight');
    });
});
</script>

<style>
/* ===== YENOLX RESTAURANT RESERVATION SYSTEM - ADMIN STYLES ===== */

/* Reset and Base Styles */
.yrr-admin-wrap * {
    box-sizing: border-box;
}

.yrr-admin-wrap {
    margin: 20px 20px 0 2px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Page Header */
.wp-heading-inline {
    margin-bottom: 10px;
    font-size: 23px;
    font-weight: 400;
    line-height: 1.3;
}

.yrr-count {
    font-weight: 300;
    color: #50575e;
    font-size: 16px;
    margin-left: 5px;
}

/* Add New Reservation Button */
.page-title-action.yrr-btn-create-reservation {
    background: #2271b1 !important;
    border-color: #2271b1 !important;
    color: #fff !important;
    text-decoration: none !important;
    display: inline-flex !important;
    align-items: center !important;
    padding: 6px 12px !important;
    font-size: 13px !important;
    line-height: 2.15384615 !important;
    border-radius: 3px !important;
    border: 1px solid !important;
    cursor: pointer !important;
    transition: all 0.1s ease-in-out !important;
    margin-left: 4px !important;
    vertical-align: top !important;
}

.page-title-action.yrr-btn-create-reservation:hover,
.page-title-action.yrr-btn-create-reservation:focus {
    background: #135e96 !important;
    border-color: #135e96 !important;
    color: #fff !important;
    transform: none !important;
    box-shadow: 0 0 0 1px #135e96 !important;
}

.page-title-action.yrr-btn-create-reservation .dashicons {
    margin-right: 4px;
    font-size: 16px;
    width: 16px;
    height: 16px;
    line-height: 1;
}

/* Separator Line */
.wp-header-end {
    margin: 20px 0;
    border: 0;
    border-bottom: 1px solid #c3c4c7;
}

/* Quick Stats Section */
.yrr-quick-stats {
    margin: 20px 0 30px;
}

.yrr-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.yrr-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    transition: box-shadow 0.1s ease-in-out;
}

.yrr-stat-card:hover {
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
}

.yrr-stat-icon {
    font-size: 28px;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    flex-shrink: 0;
}

.yrr-stat-total .yrr-stat-icon { background: #e7f3ff; }
.yrr-stat-revenue .yrr-stat-icon { background: #f0f9ff; color: #0073aa; }
.yrr-stat-pending .yrr-stat-icon { background: #fef3cd; color: #b47d00; }
.yrr-stat-guests .yrr-stat-icon { background: #f0f9ff; color: #00a32a; }

.yrr-stat-content {
    flex: 1;
    min-width: 0;
}

.yrr-stat-content h3 {
    margin: 0;
    font-size: 32px;
    font-weight: 600;
    color: #1d2327;
    line-height: 1.2;
}

.yrr-stat-content p {
    margin: 5px 0 0;
    font-size: 14px;
    color: #646970;
    font-weight: 400;
}

/* Filters Section */
.yrr-filters-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.yrr-filters-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.yrr-filter-field {
    display: flex;
    flex-direction: column;
}

.yrr-filter-field label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #1d2327;
    font-size: 14px;
}

.yrr-form-control {
    padding: 6px 8px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
    line-height: 2;
    color: #2c3338;
    background-color: #fff;
    transition: border-color 0.1s ease-in-out;
}

.yrr-form-control:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: 2px solid transparent;
}

.yrr-filters-actions {
    text-align: right;
    padding-top: 10px;
    border-top: 1px solid #f0f0f1;
    margin-top: 15px;
}

.yrr-filters-actions .button {
    margin-left: 8px;
}

/* Bulk Actions */
.yrr-bulk-actions {
    margin: 20px 0;
}

.yrr-bulk-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    padding: 12px;
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.yrr-selected-count {
    font-size: 13px;
    color: #646970;
    margin-left: 10px;
}

/* Table Container */
.yrr-reservations-table-container {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

/* Main Table Styles */
.yrr-reservations-table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
    background: #fff;
}

.yrr-reservations-table thead {
    background: #f6f7f7;
}

.yrr-reservations-table th {
    padding: 12px 10px;
    text-align: left;
    font-weight: 600;
    color: #1d2327;
    border-bottom: 1px solid #c3c4c7;
    font-size: 14px;
    position: relative;
}

.yrr-reservations-table th a {
    color: #1d2327;
    text-decoration: none;
    font-weight: 600;
}

.yrr-reservations-table th a:hover {
    color: #135e96;
}

.yrr-reservations-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #f0f0f1;
    vertical-align: top;
    font-size: 14px;
    line-height: 1.5;
}

.yrr-reservations-table tbody tr {
    transition: background-color 0.1s ease-in-out;
}

.yrr-reservations-table tbody tr:hover {
    background-color: #f6f7f7;
}

.yrr-reservations-table tbody tr:nth-child(even) {
    background-color: #fafafa;
}

.yrr-reservations-table tbody tr:nth-child(even):hover {
    background-color: #f0f0f1;
}

/* Column Specific Styles */
.column-cb {
    width: 2.2em;
    text-align: center;
}

.column-code {
    width: 120px;
}

.column-code strong a {
    color: #2271b1;
    text-decoration: none;
    font-weight: 600;
}

.column-code strong a:hover {
    color: #135e96;
}

.yrr-reservation-meta {
    font-size: 12px;
    color: #646970;
    margin-top: 4px;
}

.column-customer {
    min-width: 200px;
}

.column-customer strong {
    color: #1d2327;
    font-weight: 600;
}

.yrr-customer-details {
    margin-top: 4px;
    font-size: 12px;
    line-height: 1.4;
}

.yrr-customer-email,
.yrr-customer-phone {
    margin-bottom: 2px;
    color: #646970;
}

.yrr-customer-email a,
.yrr-customer-phone a {
    color: #2271b1;
    text-decoration: none;
}

.yrr-customer-email a:hover,
.yrr-customer-phone a:hover {
    color: #135e96;
}

.column-datetime {
    width: 150px;
}

.yrr-datetime-display {
    line-height: 1.4;
}

.yrr-date,
.yrr-time {
    margin-bottom: 2px;
    font-size: 13px;
    color: #2c3338;
}

.column-party {
    width: 100px;
    text-align: center;
}

.yrr-party-size {
    font-weight: 600;
    color: #2c3338;
}

.column-table {
    width: 100px;
    text-align: center;
}

.yrr-table-assignment {
    font-weight: 600;
    color: #2271b1;
}

.yrr-no-table {
    color: #8c8f94;
    font-style: italic;
}

.column-status {
    width: 110px;
    text-align: center;
}

/* Status Badges */
.yrr-status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid;
}

.yrr-status-pending {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.yrr-status-confirmed {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.yrr-status-completed {
    background: #d1ecf1;
    color: #0c5460;
    border-color: #bee5eb;
}

.yrr-status-cancelled {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.column-revenue {
    width: 100px;
    text-align: right;
}

.yrr-revenue-amount {
    font-weight: 600;
    color: #00a32a;
}

.yrr-no-revenue {
    color: #8c8f94;
    font-style: italic;
}

.yrr-discount-info {
    font-size: 11px;
    color: #d63384;
    margin-top: 2px;
}

.column-source {
    width: 90px;
    text-align: center;
}

.yrr-source-badge {
    font-size: 11px;
    color: #646970;
    padding: 2px 6px;
    background: #f6f7f7;
    border-radius: 4px;
    border: 1px solid #dcdcde;
}

.column-actions {
    width: 200px;
}

/* Action Buttons */
.yrr-reservation-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.yrr-reservation-actions .button {
    font-size: 11px;
    padding: 3px 6px;
    height: auto;
    line-height: 1.4;
    min-height: 0;
    border-radius: 3px;
}

.yrr-reservation-actions .button-primary {
    background: #00a32a;
    border-color: #00a32a;
    color: #fff;
}

.yrr-reservation-actions .button-primary:hover {
    background: #008a20;
    border-color: #008a20;
}

.yrr-btn-cancel {
    color: #b32d2e !important;
    border-color: #b32d2e !important;
    background: #fff !important;
}

.yrr-btn-cancel:hover {
    background: #b32d2e !important;
    color: #fff !important;
    border-color: #b32d2e !important;
}

.button-link-delete {
    color: #b32d2e !important;
    border-color: #b32d2e !important;
    background: #fff !important;
}

.button-link-delete:hover {
    background: #b32d2e !important;
    color: #fff !important;
    border-color: #b32d2e !important;
}

/* Special Requests Row */
.yrr-special-requests-row {
    background: #f6f7f7 !important;
}

.yrr-special-requests-content {
    padding: 10px 15px;
    font-style: italic;
    color: #646970;
    border-left: 3px solid #72aee6;
    background: #f0f6fc;
}

/* Empty State */
.yrr-no-reservations {
    padding: 60px 20px;
    text-align: center;
    background: #fff;
}

.yrr-empty-state {
    max-width: 400px;
    margin: 0 auto;
}

.yrr-empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.yrr-empty-state h3 {
    color: #1d2327;
    font-size: 20px;
    margin: 0 0 10px;
}

.yrr-empty-state p {
    color: #646970;
    font-size: 14px;
    margin: 0 0 20px;
    line-height: 1.5;
}

/* Loading States */
.yrr-loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}

.yrr-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #2271b1;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media screen and (max-width: 1200px) {
    .yrr-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media screen and (max-width: 768px) {
    .yrr-admin-wrap {
        margin: 10px 10px 0 0;
    }
    
    .wp-heading-inline {
        font-size: 20px;
    }
    
    .page-title-action.yrr-btn-create-reservation {
        display: block !important;
        margin: 10px 0 0 0 !important;
        text-align: center !important;
    }
    
    .yrr-stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .yrr-filters-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .yrr-bulk-controls {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    
    .yrr-reservations-table-container {
        overflow-x: auto;
    }
    
    .yrr-reservations-table {
        min-width: 800px;
    }
    
    .yrr-reservation-actions {
        flex-direction: column;
    }
    
    .yrr-reservation-actions .button {
        width: 100%;
        text-align: center;
        margin-bottom: 2px;
    }
}

@media screen and (max-width: 480px) {
    .yrr-stat-card {
        flex-direction: column;
        text-align: center;
        padding: 15px;
    }
    
    .yrr-stat-icon {
        margin-bottom: 10px;
    }
    
    .yrr-form-control {
        font-size: 16px; /* Prevent zoom on iOS */
    }
}

/* Fix for WordPress Admin Bar Overlap */
@media screen and (max-width: 782px) {
    .yrr-admin-wrap {
        margin-top: 10px;
    }
}

/* Print Styles */
@media print {
    .page-title-action,
    .yrr-bulk-actions,
    .yrr-filters-section,
    .column-actions,
    .column-cb {
        display: none !important;
    }
    
    .yrr-reservations-table {
        font-size: 10px;
        border: 1px solid #000;
    }
    
    .yrr-reservations-table th,
    .yrr-reservations-table td {
        border: 1px solid #000;
        padding: 4px;
    }
}
</style>
<script>
jQuery(document).ready(function($) {
    // Enhanced bulk checkbox handling
    $('#cb-select-all').on('change', function() {
        $('.yrr-bulk-checkbox').prop('checked', this.checked);
        updateBulkActions();
    });
    
    $('.yrr-bulk-checkbox').on('change', function() {
        updateBulkActions();
    });
    
    function updateBulkActions() {
        var checkedCount = $('.yrr-bulk-checkbox:checked').length;
        var totalBoxes = $('.yrr-bulk-checkbox').length;
        
        $('#selected-count').text(checkedCount);
        $('.yrr-selected-count').toggle(checkedCount > 0);
        $('#bulk-action-submit').prop('disabled', checkedCount === 0);
        
        // Update select all checkbox
        var $selectAll = $('#cb-select-all');
        if (checkedCount === 0) {
            $selectAll.prop('indeterminate', false).prop('checked', false);
        } else if (checkedCount === totalBoxes) {
            $selectAll.prop('indeterminate', false).prop('checked', true);
        } else {
            $selectAll.prop('indeterminate', true).prop('checked', false);
        }
    }
    
    // Bulk form submission with confirmation
    $('#yrr-bulk-form').on('submit', function(e) {
        var action = $('select[name="bulk_action"]').val();
        var checkedCount = $('.yrr-bulk-checkbox:checked').length;
        
        if (!action) {
            e.preventDefault();
            alert('Please select a bulk action.');
            return false;
        }
        
        if (checkedCount === 0) {
            e.preventDefault();
            alert('Please select at least one reservation.');
            return false;
        }
        
        if (action === 'delete') {
            if (!confirm('Are you sure you want to delete the selected reservations? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        }
        
        // Show loading state
        $(this).find('button[type="submit"]').prop('disabled', true).text('Processing...');
    });
    
    // Row highlighting with smooth transitions
    $('.yrr-reservations-table tbody tr').on('mouseenter', function() {
        $(this).addClass('yrr-row-highlight');
    }).on('mouseleave', function() {
        $(this).removeClass('yrr-row-highlight');
    });
    
    // Smooth scroll to top after form actions
    if (window.location.hash === '#success' || $('div.notice-success').length) {
        $('html, body').animate({
            scrollTop: $('.yrr-admin-wrap').offset().top - 50
        }, 500);
    }
});
</script>
