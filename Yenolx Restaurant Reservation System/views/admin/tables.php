<?php
/**
 * Tables Management View for Yenolx Restaurant Reservation System
 *
 * This file renders the interface for adding, editing, and deleting tables.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure the necessary model is available
if (!class_exists('YRR_Tables_Model')) {
    echo '<div class="notice notice-error"><p>Error: The Tables Model is missing and tables cannot be displayed.</p></div>';
    return;
}

// Handle form submissions for adding/editing a table
$edit_table = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['table_id'])) {
    $edit_table = YRR_Tables_Model::get_by_id(intval($_GET['table_id']));
}

// Fetch all tables to display in the list
$all_tables = YRR_Tables_Model::get_all();

?>

<div class="wrap yrr-tables">
    <h1><?php _e('Tables Management', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('Define the physical tables in your restaurant, including their capacity and location.', 'yrr'); ?></p>

    <div id="col-container" class="wp-clearfix">

        <!-- Left Column: Add/Edit Form -->
        <div id="col-left">
            <div class="col-wrap">
                <div class="form-wrap">
                    <h2><?php echo $edit_table ? __('Edit Table', 'yrr') : __('Add New Table', 'yrr'); ?></h2>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="<?php echo $edit_table ? 'yrr_edit_table' : 'yrr_add_table'; ?>" />
                        <input type="hidden" name="table_id" value="<?php echo esc_attr($edit_table->id ?? ''); ?>" />
                        <?php wp_nonce_field($edit_table ? 'yrr_edit_table_nonce' : 'yrr_add_table_nonce'); ?>

                        <div class="form-field">
                            <label for="table_number"><?php _e('Table Name/Number', 'yrr'); ?></label>
                            <input type="text" name="table_number" id="table_number" value="<?php echo esc_attr($edit_table->table_number ?? ''); ?>" required />
                            <p><?php _e('A unique name for the table, like "Table 5" or "Patio 2".', 'yrr'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="capacity"><?php _e('Capacity', 'yrr'); ?></label>
                            <input type="number" name="capacity" id="capacity" value="<?php echo esc_attr($edit_table->capacity ?? '2'); ?>" min="1" required />
                            <p><?php _e('The maximum number of guests this table can seat.', 'yrr'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="location"><?php _e('Location/Zone', 'yrr'); ?></label>
                            <input type="text" name="location" id="location" value="<?php echo esc_attr($edit_table->location ?? 'Main Dining Room'); ?>" />
                            <p><?php _e('The area where this table is located, e.g., "Bar Area", "Window Side".', 'yrr'); ?></p>
                        </div>

                        <?php if ($edit_table) : ?>
                            <?php submit_button(__('Update Table', 'yrr')); ?>
                            <a href="?page=yrr-tables" class="button button-secondary"><?php _e('Cancel Edit', 'yrr'); ?></a>
                        <?php else : ?>
                            <?php submit_button(__('Add New Table', 'yrr')); ?>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Tables List -->
        <div id="col-right">
            <div class="col-wrap">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-primary"><?php _e('Table Name', 'yrr'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Capacity', 'yrr'); ?></th>
                            <th scope="col" class="manage-column"><?php _e('Location', 'yrr'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_tables)) : ?>
                            <?php foreach ($all_tables as $table) : ?>
                                <tr>
                                    <td class="column-primary">
                                        <strong><?php echo esc_html($table->table_number); ?></strong>
                                        <div class="row-actions">
                                            <span class="edit"><a href="?page=yrr-tables&action=edit&table_id=<?php echo $table->id; ?>"><?php _e('Edit', 'yrr'); ?></a> | </span>
                                            <span class="delete"><a href="#" class="submitdelete"><?php _e('Delete', 'yrr'); ?></a></span>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($table->capacity); ?></td>
                                    <td><?php echo esc_html($table->location); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="3"><?php _e('No tables have been added yet.', 'yrr'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
