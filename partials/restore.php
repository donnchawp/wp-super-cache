<div class="wpsc-card">
<?php
echo '<h2>' . __( 'Fix Configuration', 'wp-super-cache' ) . '</h2>';
echo '<form name="wp_restore" action="' . esc_url_raw( add_query_arg( 'tab', 'settings', $admin_url ) . '#top' ) . '" method="post">';
echo '<input type="hidden" name="wp_restore_config" />';
submit_button( __( 'Restore Default Configuration', 'wp-super-cache' ), 'secondary' );
wp_nonce_field('wp-cache');
echo "</form>\n";
?>
</div>
