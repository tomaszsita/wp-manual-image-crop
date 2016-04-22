<?php
/*
Displays a message to warn when a conflicting plugin is detected. Provides a link to disable the conflicting plugin, and a short description of the issue.

A user may dismiss the warning which hides it permanently.
*/

add_action( 'plugins_loaded', 'mic_check_conflicting_plugins', 20 ); // Check for conflicting plugins, display to the admin

/**
 * Check for plugins known to conflict with MIC
 */
function mic_check_conflicting_plugins() {
	if ( !is_admin() ) return;
	if ( get_option( 'mic_conflicts_ignored' ) == 1 ) return; // Dismiss button has been clicked before.

	// Allow the user to dismiss conflicts by URL parameter
	if ( !empty($_REQUEST['mic_ignore_conflicts']) ) {
		update_option('mic_conflicts_ignored', 1);
		wp_redirect(remove_query_arg('mic_ignore_conflicts'));
		exit;
	}

	// Collect conflicting plugins into an array.
	$conflicting = array();

	if ( $v = _mic_find_plugin('WP Smush') ) {
		$v['reason'] = __('Will overwrite custom thumbnail cropping positions when images are "smushed".', 'microp');
		$conflicting[] = $v;
	}

	if ( empty($conflicting) ) return; // No conflicts!

	// Store conflicting plugins in a global, to carry over to the admin_notices hook.
	global $mic_conflicting_plugins;
	$mic_conflicting_plugins = $conflicting;

	add_action( 'admin_notices', '_mic_display_conflicting_plugin_error' );
}

/**
 * If the specified plugin name is found, returns information about the plugin.
 *
 * @param string $plugin_name
 *
 * @return array|bool
 */
function _mic_find_plugin( $plugin_name ) {
	static $all_plugins = null;
	if ( $all_plugins === null ) $all_plugins = get_plugins();

	// Check if plugin name or title matches, return plugin info
	foreach($all_plugins as $file => $plugin) {
		if ( !is_plugin_active( $file ) ) continue;

		if ( $plugin['Name'] == $plugin_name ) return array('file' => $file, 'plugin' => $plugin);
		else if ( $plugin['Title'] == $plugin_name ) return array('file' => $file, 'plugin' => $plugin);
	}

	return false;
}

/**
 * Displays a message to the admin about conflicting plugins.
 */
function _mic_display_conflicting_plugin_error() {
	global $mic_conflicting_plugins;
	if ( empty($mic_conflicting_plugins) ) return; // Should not occur anyway, but let's ensure we are giving a meaningful error to the user.

	?>
	<div class="error">
		<p><strong><?php echo esc_html( __('Manual Image Crop - Warning:', 'microp') ); ?></strong> <?php echo esc_html( __('The plugins listed below may conflict with this plugin and should be disabled.') ); ?></p>

		<ul class="ul-disc">
			<?php
			foreach( $mic_conflicting_plugins as $p ) {
				$plugin = $p['plugin'];
				$file = $p['file'];
				$reason = $p['reason'];

				$name = __( $plugin['Name'], $plugin['TextDomain'] );
				$version = $plugin['Version'];
				$url = $plugin['PluginURI'];
				$nonce = wp_create_nonce( 'deactivate-plugin_' . $file );

				$deactivate_url = false;
				if ( current_user_can('activate_plugins') ) {
					$deactivate_url = sprintf(
						'plugins.php?action=deactivate&plugin=%s&plugin_status=inactive&paged=1&s&_wpnonce=%s',
						urlencode($file),
						urlencode($nonce)
					);

					$deactivate_url = admin_url( $deactivate_url );
				}
				?>
				<li>
					<strong><a href="<?php echo esc_attr($url); ?>" target="_blank" rel="external"><?php echo esc_html($name); ?> <?php echo esc_html($version); ?></a></strong>
					<?php if ( $deactivate_url ) { ?>
						(<a href="<?php echo esc_attr($deactivate_url); ?>"><?php _e('Deactivate'); ?></a>)
					<?php } ?><br>
					<?php echo esc_html( $reason ); ?>
				</li>
				<?php
			}
			?>
		</ul>

		<p><a href="<?php echo esc_attr(add_query_arg('mic_ignore_conflicts', 1)); ?>" class="button button-secondary">Dismiss</a></p>
	</div>
	<?php
}