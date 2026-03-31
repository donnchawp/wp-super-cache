<div class="wpsc-card">
<?php
echo '<h2 id="useragents">' . __( 'Rejected User Agents', 'wp-super-cache' ) . '</h2>';
echo '<p class="description">' . __( 'Strings in the HTTP &#8217;User Agent&#8217; header that prevent WP-Cache from caching bot, spiders, and crawlers&#8217; requests. Note that super cached files are still sent to these agents if they already exists.', 'wp-super-cache' ) . "</p>\n";
echo '<form name="wp_edit_rejected_user_agent" action="' . esc_url_raw( add_query_arg( 'tab', 'settings', $admin_url ) . '#useragents' ) . '" method="post">';
echo '<textarea name="wp_rejected_user_agent" cols="40" rows="4" class="large-text code">';
foreach( $cache_rejected_user_agent as $ua ) {
	echo esc_html( $ua ) . "\n";
}
echo '</textarea> ';
submit_button( __( 'Save UA Strings', 'wp-super-cache' ) );
wp_nonce_field('wp-cache');
echo '</form>';

?>
</div>
