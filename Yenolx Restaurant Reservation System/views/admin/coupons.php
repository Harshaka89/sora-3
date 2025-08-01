<?php
/**
 * Coupons Management View for Yenolx Restaurant Reservation System
 *
 * This file renders the interface for adding, editing, and deleting coupons.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure the necessary model is available
if (!class_exists('YRR_Coupons_Model')) {
    echo '<div class="notice notice-error"><p>Error: The Coupons Model is missing and coupons cannot be displayed.</p></div>';
    return;
}

// Handle form submissions for adding/editing a coupon
$edit_coupon = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['coupon_id'])) {
    $edit_coupon = YRR_Coupons_Model::get_by_id(intval($_GET['coupon_id']));
}

// Fetch all coupons to display in the list
$all_coupons = YRR_Coupons_Model::get_all();

?>

<div class="wrap yrr-coupons">
    <h1><?php _e('Coupons & Promotions', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('Create and manage discount codes to offer to your customers.', 'yrr'); ?></p>

    <div id="col-container" class="wp-clearfix">

        <!-- Left Column: Add/Edit Form -->
        <div id="col-left">
            <div class="col-wrap">
                <div class="form-wrap">
                    <h2><?php echo $edit_coupon ? __('Edit Coupon', 'yrr') : __('Add New Coupon', 'yrr'); ?></h2>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="<?php echo $edit_coupon ? 'yrr_edit_coupon' : 'yrr_add_coupon'; ?>" />
                        <input type="hidden" name="coupon_id" value="<?php echo esc_attr($edit_coupon->id ?? ''); ?>" />
                        <?php wp_nonce_field($edit_coupon ? 'yrr_edit_coupon_nonce' : 'yrr_add_coupon_nonce'); ?>

                        <div class="form-field">
                            <label for="coupon_code"><?php _e('Coupon Code', 'yrr'); ?></label>
                            <input type="text" name="code" id="coupon_code" value="<?php echo esc_attr($edit_coupon->code ?? ''); ?>" required style="text-transform:uppercase" />
                            <p><?php _e('The code customers will enter. e.g., "SUMMER10".', 'yrr'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="discount_type"><?php _e('Discount Type', 'yrr'); ?></label>
                            <select name="discount_type" id="discount_type">
                                <option value="percentage" <?php selected($edit_coupon->discount_type ?? 'percentage', 'percentage'); ?>><?php _e('Percentage', 'yrr'); ?></option>
                                <option value="fixed" <?php selected($edit_coupon->discount_type ?? '', 'fixed'); ?>><?php _e('Fixed Amount', 'yrr'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label for="discount_value"><?php _e('Discount Value', 'yrr'); ?></label>
                            <input type="number" name="discount_value" id="discount_value" value="<?php echo esc_attr($edit_coupon->discount_value ?? ''); ?>" step="0.01" required />
                            <p><?php _e('The numeric value of the discount (e.g., 10 for 10% or $10).', 'yrr'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="expiry_date"><?php _e('Expiry Date (Optional)', 'yrr'); ?></label>
                            <input type="date" name="valid_to" id="expiry_date" value="<?php echo esc_attr($edit_coupon ? date('Y-m-d', strtotime($edit_coupon->valid_to)) : ''); ?>" />
                            <p><?php _e('The last day the coupon is valid.', 'yrr'); ?></p>
                        </div>

                        <?php if ($edit_coupon) : ?>
                            <?php submit_button(__('Update Coupon', 'yrr')); ?>
                            <a href="?page=yrr-coupons" class="button button-secondary"><?php _e('Cancel Edit', 'yrr'); ?></a>
                        <?php else : ?>
                            <?php submit_button(__('Add New Coupon', 'yrr')); ?>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Coupons List -->
        <div id="col-right">
            <div class="col-wrap">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-primary"><?php _e('Code', 'yrr'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Discount', 'yrr'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Usage', 'yrr'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Status', 'yrr'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_coupons)) : ?>
                            <?php foreach ($all_coupons as $coupon) : ?>
                                <tr>
                                    <td class="column-primary">
                                        <strong><?php echo esc_html($coupon->code); ?></strong>
                                        <div class="row-actions">
                                            <span class="edit"><a href="?page=yrr-coupons&action=edit&coupon_id=<?php echo $coupon->id; ?>"><?php _e('Edit', 'yrr'); ?></a> | </span>
                                            <span class="delete"><a href="#" class="submitdelete"><?php _e('Delete', 'yrr'); ?></a></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($coupon->discount_type === 'percentage') : ?>
                                            <?php echo esc_html($coupon->discount_value); ?>%
                                        <?php else : ?>
                                            <?php echo '$' . esc_html(number_format($coupon->discount_value, 2)); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($coupon->usage_count); ?> / <?php echo $coupon->usage_limit ? esc_html($coupon->usage_limit) : '&infin;'; ?></td>
                                    <td>
                                        <span class="yrr-status-badge <?php echo $coupon->is_active ? 'yrr-status-confirmed' : 'yrr-status-cancelled'; ?>">
                                            <?php echo $coupon->is_active ? __('Active', 'yrr') : __('Inactive', 'yrr'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4"><?php _e('No coupons have been created yet.', 'yrr'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
