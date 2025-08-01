<?php
/**
 * All Reservations View for Yenolx Restaurant Reservation System
 *
 * This file renders the main table for viewing and managing all reservations.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Fetch reservations data and pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$limit = 20;
$offset = ($paged - 1) * $limit;

$filters = array(
    'status' => isset($_GET['status']) ? sanitize_key($_GET['status']) : '',
    's'      => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
);

$all_reservations = YRR_Reservation_Model::get_all(array_merge($filters, ['limit' => $limit, 'offset' => $offset]));
$total_count = YRR_Reservation_Model::get_total_count($filters);
$total_pages = ceil($total_count / $limit);

?>

<div class="wrap yrr-all-reservations">
    <h1><?php _e('All Reservations', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('View, manage, and edit all customer bookings.', 'yrr'); ?></p>

    <!-- Filtering and Search -->
    <div class="wp-clearfix">
        <ul class="subsubsub">
            <!-- Filter links will go here -->
        </ul>
        <form method="get">
            <input type="hidden" name="page" value="yrr-reservations" />
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e('Search Reservations:', 'yrr'); ?></label>
                <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($filters['s']); ?>">
                <input type="submit" id="search-submit" class="button" value="<?php _e('Search Reservations', 'yrr'); ?>">
            </p>
            
            <!-- [NEW] Export Button -->
            <div class="yrr-export-button-wrapper">
                <?php
                $export_url = add_query_arg(array(
                    'action'   => 'yrr_export_reservations',
                    'status'   => $filters['status'],
                    's'        => $filters['s'],
                    '_wpnonce' => wp_create_nonce('yrr_export_nonce')
                ), admin_url('admin.php'));
                ?>
                <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">
                    <?php _e('Export to CSV', 'yrr'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Reservations Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('Customer', 'yrr'); ?></th>
                <th scope="col"><?php _e('Date & Time', 'yrr'); ?></th>
                <th scope="col"><?php _e('Party', 'yrr'); ?></th>
                <th scope="col"><?php _e('Table', 'yrr'); ?></th>
                <th scope="col"><?php _e('Status', 'yrr'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($all_reservations)) : ?>
                <?php foreach ($all_reservations as $reservation) : ?>
                    <tr>
                        <td class="column-primary">
                            <strong><?php echo esc_html($reservation->customer_name); ?></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="#"><?php _e('Edit', 'yrr'); ?></a> | </span>
                                <span class="delete"><a href="#" class="submitdelete"><?php _e('Delete', 'yrr'); ?></a></span>
                            </div>
                        </td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($reservation->reservation_date)) . ' @ ' . date('g:i A', strtotime($reservation->reservation_time))); ?></td>
                        <td><?php echo esc_html($reservation->party_size); ?></td>
                        <td><?php echo esc_html($reservation->table_number ?? 'N/A'); ?></td>
                        <td>
                             <span class="yrr-status-badge yrr-status-<?php echo esc_attr($reservation->status); ?>">
                                <?php echo esc_html(ucfirst($reservation->status)); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5"><?php _e('No reservations found.', 'yrr'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo $total_count; ?> items</span>
                <span class="pagination-links">
                    <?php echo paginate_links(array(
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total'     => $total_pages,
                        'current'   => $paged,
                    )); ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

</div>
