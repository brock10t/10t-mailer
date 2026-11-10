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

		$this->registered = true;
	}
}
