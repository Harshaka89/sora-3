<?php include_once('../../includes/auth-check.php'); ?>
<a href="../../reset-password.php" class="btn btn-outline-secondary">Change Password</a>

if (!defined('ABSPATH')) exit;
$reservations = class_exists('YRR_Reservation_Model') ? YRR_Reservation_Model::get_all() : array();
?>
<div class="wrap">
  <h1><?php _e('All Reservations', 'yrr'); ?></h1>
  <table class="wp-list-table widefat striped">
    <thead>
      <tr>
        <th><?php _e('Date', 'yrr'); ?></th>
        <th><?php _e('Time', 'yrr'); ?></th>
        <th><?php _e('Name', 'yrr'); ?></th>
        <th><?php _e('Table', 'yrr'); ?></th>
        <th><?php _e('Covers', 'yrr'); ?></th>
        <th><?php _e('Status', 'yrr'); ?></th>
        <th style="width:90px"><?php _e('Actions', 'yrr'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($reservations as $r): ?>
      <tr>
        <td><?php echo esc_html(date('M j, Y', strtotime($r->reservation_date))); ?></td>
        <td><?php echo esc_html(date('g:i A', strtotime($r->reservation_time))); ?></td>
        <td><?php echo esc_html($r->customer_name); ?></td>
        <td><?php echo esc_html($r->table_number ?? ''); ?></td>
        <td><?php echo intval($r->party_size); ?></td>
        <td>
          <span class="yrr-status-label status-<?php echo esc_attr($r->status); ?>">
            <?php echo ucfirst($r->status); ?>
          </span>
        </td>
        <td>
          <a class="button button-small" href="<?php echo admin_url('admin.php?page=yrr-reservations&view='.$r->id); ?>"><?php _e('View', 'yrr'); ?></a>
          <a class="button button-small" href="<?php echo admin_url('admin.php?page=yrr-reservations&edit='.$r->id); ?>"><?php _e('Edit', 'yrr'); ?></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
