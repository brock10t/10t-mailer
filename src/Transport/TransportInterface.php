<?php
/**
 * Mail transport contract.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer\Transport;

use TenT\Mailer\Configuration\Configuration;

/**
 * Defines the common behavior for mail delivery integrations.
 *
 * Transports register the WordPress hooks appropriate to their delivery
 * method. This allows an SMTP transport to configure PHPMailer while a future
 * API transport can integrate through a different WordPress mail hook.
 */
interface TransportInterface {

	/**
	 * Returns the stable identifier used by the transport configuration.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Determines whether the transport can run with the current configuration.
	 *
	 * This check must not register hooks or otherwise change runtime behavior.
	 *
	 * @param Configuration $configuration Effective configuration.
	 * @return bool
	 */
	public function is_available( Configuration $configuration ): bool;

	/**
	 * Registers the transport's WordPress integration.
	 *
	 * This method is called only for the selected, enabled transport after it
	 * has been found to be available.
	 *
	 * @param Configuration $configuration Effective configuration.
	 * @return void
	 */
	public function register( Configuration $configuration ): void;
}
