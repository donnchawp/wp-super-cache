<div class="wpsc-settings-inner">
<?php
global $wp_cache_preload_posts;

echo '<div class="wpsc-card" id="preload">';
if ( ! $cache_enabled || ! $super_cache_enabled || true === defined( 'DISABLESUPERCACHEPRELOADING' ) ) {
	wp_admin_notice(
		esc_html__( 'Preloading of cache disabled. Please make sure simple or expert mode is enabled or talk to your host administrator.', 'wp-super-cache' ),
		array(
			'type' => 'warning',
		)
	);
	return;
}

$count = wpsc_post_count();

$min_refresh_interval = wpsc_get_minimum_preload_interval();

echo '<p>' . __( 'This will cache every published post and page on your site. It will create supercache static files so unknown visitors (including bots) will hit a cached page. This will probably help your Google ranking as they are using speed as a metric when judging websites now.', 'wp-super-cache' ) . '</p>';
echo '<p>' . __( 'Preloading creates lots of files however. Caching is done from the newest post to the oldest so please consider only caching the newest if you have lots (10,000+) of posts. This is especially important on shared hosting.', 'wp-super-cache' ) . '</p>';
echo '<p>' . __( 'In &#8217;Preload Mode&#8217; regular garbage collection will be disabled so that old cache files are not deleted. This is a recommended setting when the cache is preloaded.', 'wp-super-cache' ) . '</p>';
echo '<form name="cache_filler" action="' . esc_url_raw( add_query_arg( 'tab', 'preload', $admin_url ) ) . '" method="POST">';
echo '<input type="hidden" name="action" value="preload" />';
echo '<input type="hidden" name="page" value="wpsupercache" />';
echo '</div>';
echo '<div class="wpsc-card">';
echo '<p>' . sprintf( __( 'Refresh preloaded cache files every %s minutes. (0 to disable, minimum %d minutes.)', 'wp-super-cache' ), "<input type='text' size=4 name='wp_cache_preload_interval' value='" . (int) $wp_cache_preload_interval . "' />", $min_refresh_interval ) . '</p>';
if ( $count > 100 ) {
	$step = (int)( $count / 10 );

	$select = "<select name='wp_cache_preload_posts' size=1>";
	$select .= "<option value='all' ";
	if ( ! isset( $wp_cache_preload_posts ) || $wp_cache_preload_posts == 'all' ) {
		$checked = 'selectect=1 ';
		$best = 'all';
	} else {
		$checked = ' ';
		$best = $wp_cache_preload_posts;
	}
	$select .= "{$checked}>" . __( 'all', 'wp-super-cache' ) . "</option>";

	$options = array();
	for( $c = $step; $c < $count; $c += $step ) {
		$checked = ' ';
		if ( $best == $c )
			$checked = 'selected=1 ';

		$options[ $c ] = "<option value='$c'{$checked}>$c</option>";
	}

	if ( ! isset( $options[ $wp_cache_preload_posts ] ) ) {
		$options[ $wp_cache_preload_posts ] = "<option value='$wp_cache_preload_posts' selected=1>$wp_cache_preload_posts</option>";
	}
	ksort( $options );
	$select .= implode( "\n", $options );

	$checked = ' ';
	if ( $best == $count )
		$checked = 'selected=1 ';
	$select .= "<option value='$count'{$checked}>$count</option>";
	$select .= "</select>";
	echo '<p>' . sprintf( __( 'Preload %s posts.', 'wp-super-cache' ), $select ) . '</p>';
} else {
	echo '<input type="hidden" name="wp_cache_preload_posts" value="' . $count . '" />';
}

echo '<label><input type="checkbox" name="wp_cache_preload_on" value="1" ' . checked( 1, $wp_cache_preload_on, false ) . ' /> ' . __( 'Preload mode (garbage collection disabled. Recommended.)', 'wp-super-cache' ) . '</label><br />';
echo '<label><input type="checkbox" name="wp_cache_preload_taxonomies" value="1" ' . checked( 1, $wp_cache_preload_taxonomies, false ) . ' /> ' . __( 'Preload tags, categories and other taxonomies.', 'wp-super-cache' ) . '</label><br />';
echo __( 'Send me status emails when files are refreshed.', 'wp-super-cache' ) . '<br />';
if ( !isset( $wp_cache_preload_email_volume ) )
	$wp_cache_preload_email_volume = 'none';
echo '<select type="select" name="wp_cache_preload_email_volume">';
echo '<option value="none" ' . selected( 'none', $wp_cache_preload_email_volume, false ) . '>' . esc_html__( 'No Emails', 'wp-super-cache' ) . '</option>';
// translators: %d is the number of posts
echo '<option value="many" ' . selected( 'many', $wp_cache_preload_email_volume, false ) . '>' . esc_html( sprintf( __( 'Many emails, 2 emails per %d posts.', 'wp-super-cache' ), WPSC_PRELOAD_POST_COUNT ) ) . '</option>';
// translators: %d is the number of posts
echo '<option value="medium" ' . selected( 'medium', $wp_cache_preload_email_volume, false ) . '>' . esc_html( sprintf( __( 'Medium, 1 email per %d posts.', 'wp-super-cache' ), WPSC_PRELOAD_POST_COUNT ) ) . '</option>';
echo '<option value="less" ' . selected( 'less', $wp_cache_preload_email_volume, false ) . '>' . esc_html__( 'Less emails, 1 at the start and 1 at the end of preloading all posts.', 'wp-super-cache' ) . '</option>';
echo "</select>";

if (
	wp_next_scheduled( 'wp_cache_preload_hook' )
	|| wp_next_scheduled( 'wp_cache_full_preload_hook' )
	|| wpsc_is_preload_active()
) {
	$currently_preloading = true;
}
submit_button( __( 'Save Settings', 'wp-super-cache' ), 'primary', 'preload' );
wp_nonce_field( 'wp-cache' );
echo '</form>';
echo '<form name="do_preload" action="' . esc_url_raw( add_query_arg( 'tab', 'preload', $admin_url ) ) . '" method="POST">';
echo '<input type="hidden" name="action" value="preload" />';
echo '<input type="hidden" name="page" value="wpsupercache" />';
if ( false == $currently_preloading ) {
	submit_button( __( 'Preload Cache Now', 'wp-super-cache' ), 'primary', 'preload_now' );
} else {
	submit_button( __( 'Cancel Cache Preload', 'wp-super-cache' ), 'primary', 'preload_off' );
}
wp_nonce_field( 'wp-cache' );
echo '</form>';
echo '</div>';
echo '</div>';
