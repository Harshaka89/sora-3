<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php esc_html_e('Restaurant Settings', 'yrr'); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('yrr_settings_group');
        do_settings_sections('yrr-settings');
        submit_button();
        ?>
    </form>
</div>
