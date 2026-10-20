<?php
// admin/fleetwire_notice.php

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="notice notice-error is-dismissible">
    <h2><?php esc_html_e('Fleetwire Setup Required', 'fleetwire-fleet-management'); ?></h2>
    <p>
        <strong><?php esc_html_e('Fleetwire Online Rental Shop', 'fleetwire-fleet-management'); ?></strong>
        <?php esc_html_e('requires setup. Please configure it in the', 'fleetwire-fleet-management'); ?>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=fleetwire#settings' ) ); ?>"><?php esc_html_e('Plugin Settings', 'fleetwire-fleet-management'); ?></a>.
    </p>
</div>