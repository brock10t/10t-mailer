<?php
/**
 * Main plugin coordinator.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer;

use TenT\Mailer\Admin\SettingsPage;
use TenT\Mailer\Configuration\Configuration;
use TenT\Mailer\Configuration\Keys;
use TenT\Mailer\Configuration\Resolver;
use TenT\Mailer\Transport\SmtpTransport;
use TenT\Mailer\Transport\TransportInterface;

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
	 * Transport registered for the current request.
	 *
	 * @var TransportInterface|null
	 */
	private ?TransportInterface $transport = null;

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
		$this->register_transport();

		if ( is_admin() ) {
			$this->settings_page = new SettingsPage();
			$this->settings_page->register();
		}

		$this->registered = true;
	}

	/**
	 * Registers the selected transport when it is enabled and available.
	 *
	 * @return void
	 */
	private function register_transport(): void {
		if ( null === $this->configuration || true !== $this->configuration->get( Keys::ENABLED ) ) {
			return;
		}

		$selected_transport = $this->configuration->get( Keys::TRANSPORT );

		foreach ( $this->available_transports() as $transport ) {
			if ( $selected_transport !== $transport->get_id() || ! $transport->is_available( $this->configuration ) ) {
				continue;
			}

			$transport->register( $this->configuration );
			$this->transport = $transport;
			return;
		}
	}

	/**
	 * Returns the delivery transports known to the plugin.
	 *
	 * @return TransportInterface[]
	 */
	private function available_transports(): array {
		return array(
			new SmtpTransport(),
		);
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
