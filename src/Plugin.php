<?php
/**
 * Main plugin coordinator.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer;

/**
 * Registers the plugin's runtime services with WordPress.
 */
final class Plugin {

	/**
	 * Whether the plugin's services have already been registered.
	 *
	 * @var bool
	 */
	private bool $registered = false;

	/**
	 * Registers runtime services and hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade_database' ) );

		$this->registered = true;
	}

	/**
	 * Runs pending database schema upgrades.
	 *
	 * @return void
	 */
	public function maybe_upgrade_database(): void {
		\tent_mailer_create_database_tables();
	}
}
