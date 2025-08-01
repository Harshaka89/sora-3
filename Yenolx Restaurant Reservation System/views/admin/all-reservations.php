<?php
/**
 * All Reservations View for Yenolx Restaurant Reservation System
 *
 * This file renders the main reservations management table, including
 * filters for date, status, and a search box.
 *
 * @package YRR/Views
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure the necessary model is available
if (!class_exists('YRR_Reservation_Model')) {
    echo '<div class="notice notice-error"><p>Error: The Reservation Model is missing and reservations cannot be displayed.</p></div>';
    return;
}

// --- Data Fetching and Pagination ---

$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$limit = 20; // Number of items per page
$offset = ($paged - 1) * $limit;

// --- Filtering ---

$filters = array(
    'status' => isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '',
    'search' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
);

$reservations = YRR_Reservation_Model::get_all(array(
    'limit'  => $limit,
    'offset' => $offset,
    'status' => $filters['status'],
    'search' => $filters['search']
));

$total_reservations = YRR_Reservation_Model::get_total_count($filters);
$total_pages = ceil($total_reservations / $limit);

?>

<div class="wrap yrr-all-reservations">
    <h1><?php _e('All Reservations', 'yrr'); ?></h1>
    <p class="yrr-page-description"><?php _e('Manage all customer bookings from this central table.', 'yrr'); ?></p>

    <!-- Filter and Search Form -->
    <form method="get">
        <input type="hidden" name="page" value="yrr-reservations" />
        
        <div class="yrr-filters">
            <!-- Status Filter Links -->
            <ul class="subsubsub">
                <li><a href="?page=yrr-reservations" class="<?php if (empty($filters['status'])) echo 'current'; ?>"><?php _e('All', 'yrr'); ?></a> |</li>
                <li><a href="?page=yrr-reservations&status=confirmed" class="<?php if ($filters['status'] === 'confirmed') echo 'current'; ?>"><?php _e('Confirmed', 'yrr'); ?></a> |</li>
                <li><a href="?page=yrr-reservations&status=pending" class="<?php if ($filters['status'] === 'pending') echo 'current'; ?>"><?php _e('Pending', 'yrr'); ?></a> |</li>
                <li><a href="?page=yrr-reservations&status=cancelled" class="<?php if ($filters['status'] === 'cancelled') echo 'current'; ?>"><?php _e('Cancelled', 'yrr'); ?></a></li>
            </ul>

            <!-- Search Box -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e('Search Reservations:', 'yrr'); ?></label>
                <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($filters['search']); ?>">
                <input type="submit" id="search-submit" class="button" value="<?php _e('Search Reservations', 'yrr'); ?>">
            </p>
        </div>
    </form>
    
    <!-- Reservations Table -->
    <div class="yrr-table-wrapper">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-primary"><?php _e('Customer', 'yrr'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Date & Time', 'yrr'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Party Size', 'yrr'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Assigned Table', 'yrr'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Status', 'yrr'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('Actions', 'yrr'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
                <?php if (!empty($reservations)) : ?>
                    <?php foreach ($reservations as $reservation) : ?>
                        <tr>
                            <td class="column-primary">
                                <strong><?php echo esc_html($reservation->customer_name); ?></strong>
                                <br>
                                <small><?php echo esc_html($reservation->customer_email); ?></small>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e('Show more details', 'yrr'); ?></span></button>
                            </td>
                            <td>
                                <?php echo date_i18n(get_option('date_format'), strtotime($reservation->reservation_date)); ?>
                                <br>
                                <?php echo date_i18n(get_option('time_format'), strtotime($reservation->reservation_time)); ?>
                            </td>
                            <td><?php echo esc_html($reservation->party_size); ?></td>
                            <td><?php echo $reservation->table_id ? esc_html($reservation->table_number) : __('Not Assigned', 'yrr'); ?></td>
                            <td>
                                <span class="yrr-status-badge yrr-status-<?php echo esc_attr($reservation->status); ?>">
                                    <?php echo esc_html(ucfirst($reservation->status)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="#" class="button button-small"><?php _e('View/Edit', 'yrr'); ?></a>
                                <a href="#" class="button button-small button-link-delete"><?php _e('Cancel', 'yrr'); ?></a>
                            </td>
