<?php
/**
 * Plugin Name:       10T Mailer
 * Plugin URI:        https://10t.co/
 * Description:       Routes WordPress email through configurable delivery services.
 * Version:           0.0.1
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            10T Web Design
 * Author URI:        https://10t.co/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       10t-mailer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TENT_MAILER_DATABASE_VERSION', '1' );

/**
 * Loads classes in the TenT\Mailer namespace from the src directory.
 *
 * @param string $class_name Fully qualified class name.
 * @return void
 */
function tent_mailer_autoload( string $class_name ): void {
	$namespace_prefix = 'TenT\\Mailer\\';

	if ( ! str_starts_with( $class_name, $namespace_prefix ) ) {
		return;
	}

	$relative_class = substr( $class_name, strlen( $namespace_prefix ) );
	$file           = __DIR__ . '/src/' . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( is_readable( $file ) ) {
		require_once $file;
	}
}

/**
 * Creates or updates the plugin's database tables.
 *
 * The log table is shared across a multisite network. Access to its records
 * must be scoped by blog ID unless the current user has network-level access.
 *
 * @return void
 */
function tent_mailer_create_database_tables(): void {
	global $wpdb;

	$installed_version = get_site_option( 'tent_mailer_database_version', '0' );

	if ( TENT_MAILER_DATABASE_VERSION === $installed_version ) {
		return;
	}

	$table_name      = $wpdb->base_prefix . '10t_mailer_logs';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		blog_id bigint(20) unsigned NOT NULL DEFAULT 0,
		created_at datetime NOT NULL,
		level varchar(20) NOT NULL,
		event varchar(100) NOT NULL,
		component varchar(64) NOT NULL,
		message text NOT NULL,
		correlation_id varchar(64) DEFAULT NULL,
		transport varchar(32) DEFAULT NULL,
		status varchar(32) DEFAULT NULL,
		duration_ms int(10) unsigned DEFAULT NULL,
		context longtext DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY blog_created (blog_id, created_at),
		KEY created_at (created_at),
		KEY level (level),
		KEY event (event),
		KEY correlation_id (correlation_id)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_site_option( 'tent_mailer_database_version', TENT_MAILER_DATABASE_VERSION );

	$logger = new TenT\Mailer\Logging\Logger();
	$logger->log(
		TenT\Mailer\Logging\Logger::INFO,
		'database.upgraded',
		'database',
		'10T Mailer database upgraded to version ' . TENT_MAILER_DATABASE_VERSION . '.',
		array(
			'from_version' => (string) $installed_version,
			'to_version'   => TENT_MAILER_DATABASE_VERSION,
		),
		status: 'completed'
	);
}

/**
 * Runs when the plugin is activated.
 *
 * @param bool $network_wide Whether the plugin is being activated for the
 *                           entire multisite network.
 * @return void
 */
function tent_mailer_activate( bool $network_wide = false ): void {
	tent_mailer_create_database_tables();

	$logger = new TenT\Mailer\Logging\Logger();
	$logger->log(
		TenT\Mailer\Logging\Logger::INFO,
		'plugin.activated',
		'plugin',
		'10T Mailer activated.',
		array( 'network_wide' => $network_wide ),
		status: 'completed'
	);
}

/**
 * Runs when the plugin is deactivated.
 *
 * Deactivation intentionally preserves settings and other stored data.
 *
 * @param bool $network_wide Whether the plugin is being deactivated for the
 *                           entire multisite network.
 * @return void
 */
function tent_mailer_deactivate( bool $network_wide = false ): void {
	$logger = new TenT\Mailer\Logging\Logger();
	$logger->log(
		TenT\Mailer\Logging\Logger::INFO,
		'plugin.deactivated',
		'plugin',
		'10T Mailer deactivated.',
		array( 'network_wide' => $network_wide ),
		status: 'completed'
	);
}

register_activation_hook( __FILE__, 'tent_mailer_activate' );
register_deactivation_hook( __FILE__, 'tent_mailer_deactivate' );

spl_autoload_register( 'tent_mailer_autoload' );

( new TenT\Mailer\Plugin() )->register();
