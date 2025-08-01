<?php
/**
 * Locations Management View for Yenolx Restaurant Reservation System
 *
 * This file renders the interface for adding, editing, and managing multiple
 * restaurant locations or branches.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure the necessary model is available
if (!class_exists('YRR_Locations_Model')) {
    echo '<div class="notice notice-error"><p>Error: The Locations Model is missing and locations cannot be displayed.</p></div>';
    return;
}

// Handle form submissions for adding/editing a location
$edit_location = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['location_id'])) {
    $edit_location = YRR_Locations_Model::get_by_id(intval($_GET['location_id']));
}

// Fetch all locations to display in the list
$all_locations = YRR_Locations_Model::get_all();

?>

<div class="wrap yrr-locations">
    <h1><?php _e('Locations Management', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('Manage all your restaurant branches. Each location can have its own tables and hours.', 'yrr'); ?></p>

    <div id="col-container" class="wp-clearfix">

        <!-- Left Column: Add/Edit Form -->
        <div id="col-left">
            <div class="col-wrap">
                <div class="form-wrap">
                    <h2><?php echo $edit_location ? __('Edit Location', 'yrr') : __('Add New Location', 'yrr'); ?></h2>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="<?php echo $edit_location ? 'yrr_edit_location' : 'yrr_add_location'; ?>" />
                        <input type="hidden" name="location_id" value="<?php echo esc_attr($edit_location->id ?? ''); ?>" />
                        <?php wp_nonce_field($edit_location ? 'yrr_edit_location_nonce' : 'yrr_add_location_nonce'); ?>

                        <div class="form-field">
                            <label for="location_name"><?php _e('Location Name', 'yrr'); ?></label>
                            <input type="text" name="name" id="location_name" value="<?php echo esc_attr($edit_location->name ?? ''); ?>" required />
                            <p><?php _e('The name of the restaurant branch, e.g., "Downtown Bistro".', 'yrr'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="location_address"><?php _e('Address', 'yrr'); ?></label>
                            <textarea name="address" id="location_address" rows="3"><?php echo esc_textarea($edit_location->address ?? ''); ?></textarea>
                            <p><?php _e('The physical address of this location.', 'yrr'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="location_phone"><?php _e('Phone Number', 'yrr'); ?></label>
                            <input type="text" name="phone" id="location_phone" value="<?php echo esc_attr($edit_location->phone ?? ''); ?>" />
                        </div>
                        
                        <div class="form-field">
                            <label for="location_email"><?php _e('Public Email', 'yrr'); ?></label>
                            <input type="email" name="email" id="location_email" value="<?php echo esc_attr($edit_location->email ?? ''); ?>" />
                        </div>

                        <?php if ($edit_location) : ?>
                            <?php submit_button(__('Update Location', 'yrr')); ?>
                            <a href="?page=yrr-locations" class="button button-secondary"><?php _e('Cancel Edit', 'yrr'); ?></a>
                        <?php else : ?>
                            <?php submit_button(__('Add New Location', 'yrr')); ?>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Locations List -->
        <div id="col-right">
            <div class="col-wrap">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-primary"><?php _e('Location Name', 'yrr'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Address', 'yrr'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Tables', 'yrr'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_locations)) : ?>
                            <?php foreach ($all_locations as $location) : ?>
                                <tr>
                                    <td class="column-primary">
                                        <strong><?php echo esc_html($location->name); ?></strong>
                                        <div class="row-actions">
                                            <span class="edit"><a href="?page=yrr-locations&action=edit&location_id=<?php echo $location->id; ?>"><?php _e('Edit', 'yrr'); ?></a> | </span>
                                            <span class="delete"><a href="#" class="submitdelete"><?php _e('Delete', 'yrr'); ?></a></span>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($location->address); ?></td>
                                    <td><?php echo esc_html(YRR_Tables_Model::get_count_by_location($location->id)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="3"><?php _e('No locations have been added yet.', 'yrr'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
