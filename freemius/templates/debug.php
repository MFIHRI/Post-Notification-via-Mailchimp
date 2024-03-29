<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.1.1
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	global $fs_active_plugins;

	$fs_options = FS_Option_Manager::get_manager( WP_FS__ACCOUNTS_OPTION_NAME, true );
?>
	<h1><?php echo __fs( 'Freemius Debug' ) . ' - ' . __fs( 'SDK' ) . ' v.' . $fs_active_plugins->newest->version ?></h1>
	<div>
		<!-- Debugging Switch -->
		<?php //$debug_mode = get_option( 'fs_debug_mode', null ) ?>
		<span class="switch-label"><?php _efs( 'debugging' ) ?></span>

		<div class="switch <?php echo WP_FS__DEBUG_SDK ? 'off' : 'on' ?>">
			<div class="toggle"></div>
			<span class="on"><?php _efs( 'on' ) ?></span>
			<span class="off"><?php _efs( 'off' ) ?></span>
		</div>
		<script type="text/javascript">
			(function ($) {
				$(document).ready(function () {
					// Switch toggle
					$('.switch').click(function () {
						$(this)
							.toggleClass('on')
							.toggleClass('off');

						$.post(ajaxurl, {
							action: 'fs_toggle_debug_mode',
							is_on : ($(this).hasClass('off') ? 1 : 0)
						}, function (response) {
							if (1 == response) {
								// Refresh page on success.
								location.reload();
							}
						});
					});
				});
			}(jQuery));
		</script>
	</div>
	<h2><?php _efs( 'actions' ) ?></h2>
	<table>
		<tbody>
		<tr>
			<td>
				<!-- Delete All Accounts -->
				<form action="" method="POST">
					<input type="hidden" name="fs_action" value="restart_freemius">
					<?php wp_nonce_field( 'restart_freemius' ) ?>
					<button class="button button-primary"
					        onclick="if (confirm('<?php _efs( 'delete-all-confirm' ) ?>')) this.parentNode.submit(); return false;"><?php _efs( 'delete-all-accounts' ) ?></button>
				</form>
			</td>
			<td>
				<!-- Clear API Cache -->
				<form action="" method="POST">
					<input type="hidden" name="fs_clear_api_cache" value="true">
					<button class="button button-primary"><?php _efs( 'clear-api-cache' ) ?></button>
				</form>
			</td>
			<td>
				<!-- Sync Data with Server -->
				<form action="" method="POST">
					<input type="hidden" name="background_sync" value="true">
					<button class="button button-primary"><?php _efs( 'sync-data-from-server' ) ?></button>
				</form>
			</td>
		</tr>
		</tbody>
	</table>
<?php
	if ( ! defined( 'FS_API__ADDRESS' ) ) {
		define( 'FS_API__ADDRESS', '://api.freemius.com' );
	}
	if ( ! defined( 'FS_API__SANDBOX_ADDRESS' ) ) {
		define( 'FS_API__SANDBOX_ADDRESS', '://sandbox-api.freemius.com' );
	}

	$defines = array(
		array(
			'key' => 'WP_FS__REMOTE_ADDR',
			'val' => WP_FS__REMOTE_ADDR,
		),
		array(
			'key' => 'WP_FS__ADDRESS_PRODUCTION',
			'val' => WP_FS__ADDRESS_PRODUCTION,
		),
		array(
			'key' => 'FS_API__ADDRESS',
			'val' => FS_API__ADDRESS,
		),
		array(
			'key' => 'FS_API__SANDBOX_ADDRESS',
			'val' => FS_API__SANDBOX_ADDRESS,
		),
		array(
			'key' => 'WP_FS__DIR',
			'val' => WP_FS__DIR,
		),
	)
?>
	<br>
	<table class="widefat">
		<thead>
		<tr>
			<th><?php _efs( 'key' ) ?></th>
			<th><?php _efs( 'value' ) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php $alternate = false;
			foreach ( $defines as $p ) : ?>
				<tr<?php if ( $alternate ) {
					echo ' class="alternate"';
				} ?>>
					<td><?php echo $p['key'] ?></td>
					<td><?php echo $p['val'] ?></td>
				</tr>
				<?php $alternate = ! $alternate ?>
			<?php endforeach ?>
		</tbody>
	</table>
	<h2><?php _efs( 'sdk-versions' ) ?></h2>
	<table id="fs_sdks" class="widefat">
		<thead>
		<tr>
			<th><?php _efs( 'version' ) ?></th>
			<th><?php _efs( 'sdk-path' ) ?></th>
			<th><?php _efs( 'plugin-path' ) ?></th>
			<th><?php _efs( 'is-active' ) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $fs_active_plugins->plugins as $sdk_path => &$data ) : ?>
			<?php $is_active = ( WP_FS__SDK_VERSION == $data->version ) ?>
			<tr<?php if ( $is_active ) {
				echo ' style="background: #E6FFE6; font-weight: bold"';
			} ?>>
				<td><?php echo $data->version ?></td>
				<td><?php echo $sdk_path ?></td>
				<td><?php echo $data->plugin_path ?></td>
				<td><?php echo ( $is_active ) ? 'Active' : 'Inactive' ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
<?php $plugins = $fs_options->get_option( 'plugins' ) ?>
<?php if ( is_array( $plugins ) && 0 < count( $plugins ) ) : ?>
	<h2><?php _efs( 'plugins' ) ?></h2>
	<table id="fs_plugins" class="widefat">
		<thead>
		<tr>
			<th><?php _efs( 'id' ) ?></th>
			<th><?php _efs( 'slug' ) ?></th>
			<th><?php _efs( 'version' ) ?></th>
			<th><?php _efs( 'title' ) ?></th>
			<th><?php _efs( 'api' ) ?></th>
			<th><?php _efs( 'freemius-state' ) ?></th>
			<th><?php _efs( 'plugin-path' ) ?></th>
			<th><?php _efs( 'public-key' ) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $plugins as $slug => $data ) : ?>
			<?php $is_active = is_plugin_active( $data->file ) ?>
			<?php $fs = $is_active ? freemius( $slug ) : null ?>
			<tr<?php if ( $is_active ) {
				echo ' style="background: #E6FFE6; font-weight: bold"';
			} ?>>
				<td><?php echo $data->id ?></td>
				<td><?php echo $slug ?></td>
				<td><?php echo $data->version ?></td>
				<td><?php echo $data->title ?></td>
				<td><?php if ( $is_active ) {
						echo $fs->has_api_connectivity() ?
							__fs( 'connected' ) :
							__fs( 'blocked' );
					} ?></td>
				<td><?php if ( $is_active ) {
						echo $fs->is_on() ?
							__fs( 'on' ) :
							__fs( 'off' );
					} ?></td>
				<td><?php echo $data->file ?></td>
				<td><?php echo $data->public_key ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
<?php endif ?>
<?php
	/**
	 * @var array     $VARS
	 * @var FS_Site[] $sites
	 */
	$sites = $VARS['sites'];
?>
<?php if ( is_array( $sites ) && 0 < count( $sites ) ) : ?>
	<h2><?php _efs( 'plugin-installs' ) ?> / <?php _efs( 'sites' ) ?></h2>
	<table id="fs_installs" class="widefat">
		<thead>
		<tr>
			<th><?php _efs( 'id' ) ?></th>
			<th><?php _efs( 'slug' ) ?></th>
			<th><?php _efs( 'plan' ) ?></th>
			<th><?php _efs( 'public-key' ) ?></th>
			<th><?php _efs( 'secret-key' ) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $sites as $slug => $site ) : ?>
			<tr>
				<td><?php echo $site->id ?></td>
				<td><?php echo $slug ?></td>
				<td><?php
						echo is_object( $site->plan ) ?
							base64_decode( $site->plan->name ) :
							''
					?></td>
				<td><?php echo $site->public_key ?></td>
				<td><?php echo $site->secret_key ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
<?php endif ?>
<?php
	$addons = $VARS['addons'];
?>
<?php foreach ( $addons as $plugin_id => $plugin_addons ) : ?>
	<h2><?php printf( __fs( 'addons-of-x' ), $plugin_id ) ?></h2>
	<table id="fs_addons" class="widefat">
		<thead>
		<tr>
			<th><?php _efs( 'id' ) ?></th>
			<th><?php _efs( 'title' ) ?></th>
			<th><?php _efs( 'slug' ) ?></th>
			<th><?php _efs( 'version' ) ?></th>
			<th><?php _efs( 'public-key' ) ?></th>
			<th><?php _efs( 'secret-key' ) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
			/**
			 * @var FS_Plugin[] $plugin_addons
			 */
			foreach ( $plugin_addons as $addon ) : ?>
				<tr>
					<td><?php echo $addon->id ?></td>
					<td><?php echo $addon->title ?></td>
					<td><?php echo $addon->slug ?></td>
					<td><?php echo $addon->version ?></td>
					<td><?php echo $addon->public_key ?></td>
					<td><?php echo $addon->secret_key ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
<?php endforeach ?>
<?php
	/**
	 * @var FS_User[] $users
	 */
	$users = $VARS['users'];
?>
<?php if ( is_array( $users ) && 0 < count( $users ) ) : ?>
	<h2><?php _efs( 'users' ) ?></h2>
	<table id="fs_users" class="widefat">
		<thead>
		<tr>
			<th><?php _efs( 'id' ) ?></th>
			<th><?php _efs( 'name' ) ?></th>
			<th><?php _efs( 'email' ) ?></th>
			<th><?php _efs( 'verified' ) ?></th>
			<th><?php _efs( 'public-key' ) ?></th>
			<th><?php _efs( 'secret-key' ) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $users as $user_id => $user ) : ?>
			<tr>
				<td><?php echo $user->id ?></td>
				<td><?php echo $user->get_name() ?></td>
				<td><a href="mailto:<?php echo esc_attr( $user->email ) ?>"><?php echo $user->email ?></a></td>
				<td><?php echo json_encode( $user->is_verified ) ?></td>
				<td><?php echo $user->public_key ?></td>
				<td><?php echo $user->secret_key ?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
<?php endif ?>
<?php
	/**
	 * @var FS_Plugin_License[] $licenses
	 */
	$licenses = $VARS['licenses'];
?>
<?php if ( is_array( $licenses ) && 0 < count( $licenses ) ) : ?>
	<h2><?php _efs( 'licenses' ) ?></h2>
	<table id="fs_users" class="widefat">
		<thead>
		<tr>
			<th><?php _efs( 'id' ) ?></th>
			<th><?php _efs( 'plugin-id' ) ?></th>
			<th><?php _efs( 'user-id' ) ?></th>
			<th><?php _efs( 'plan-id' ) ?></th>
			<th><?php _efs( 'quota' ) ?></th>
			<th><?php _efs( 'activated' ) ?></th>
			<th><?php _efs( 'blocking' ) ?></th>
			<th><?php _efs( 'license-key' ) ?></th>
			<th><?php _efs( 'expiration' ) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $licenses as $slug => $module_licenses ) : ?>
			<?php foreach ( $module_licenses as $id => $licenses ) : ?>
				<?php if ( is_array( $licenses ) && 0 < count( $licenses ) ) : ?>
					<?php foreach ( $licenses as $license ) : ?>
						<tr>
							<td><?php echo $license->id ?></td>
							<td><?php echo $license->plugin_id ?></td>
							<td><?php echo $license->user_id ?></td>
							<td><?php echo $license->plan_id ?></td>
							<td><?php echo $license->is_unlimited() ? 'Unlimited' : ( $license->is_single_site() ? 'Single Site' : $license->quota ) ?></td>
							<td><?php echo $license->activated ?></td>
							<td><?php echo $license->is_block_features ? 'Blocking' : 'Flexible' ?></td>
							<td><?php echo htmlentities( $license->secret_key ) ?></td>
							<td><?php echo $license->expiration ?></td>
						</tr>
					<?php endforeach ?>
				<?php endif ?>
			<?php endforeach ?>
		<?php endforeach ?>
		</tbody>
	</table>
<?php endif ?>