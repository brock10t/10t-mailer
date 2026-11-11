<?php
/**
 * Main plugin coordinator.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer;

use TenT\Mailer\Admin\SettingsPage;
use TenT\Mailer\Configuration\Configuration;
use TenT\Mailer\Configuration\Resolver;

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
	 * Plugin settings page.
	 *
	 * @var SettingsPage|null
	 */
	private ?SettingsPage $settings_page = null;

	/**
	 * Effective configuration for the current site.
	 *
	 * @var Configuration|null
	 */
	private ?Configuration $configuration = null;

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
		$this->configuration = ( new Resolver() )->resolve();

		if ( is_admin() ) {
			$this->settings_page = new SettingsPage();
			$this->settings_page->register();
		}

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
