<?php
/**
 * SMTP mail transport.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer\Transport;

use PHPMailer\PHPMailer\PHPMailer;
use TenT\Mailer\Configuration\Configuration;
use TenT\Mailer\Configuration\SmtpConfigurationValidator;

/**
 * Registers SMTP delivery through WordPress's bundled PHPMailer instance.
 */
final class SmtpTransport implements TransportInterface {

	public const ID = 'smtp';

	/**
	 * Whether this transport has registered its WordPress hooks.
	 *
	 * @var bool
	 */
	private bool $registered = false;

	/**
	 * Effective configuration for the current request.
	 *
	 * @var Configuration|null
	 */
	private ?Configuration $configuration = null;

	/**
	 * SMTP configuration validator.
	 *
	 * @var SmtpConfigurationValidator
	 */
	private SmtpConfigurationValidator $validator;

	/**
	 * Creates the SMTP transport.
	 *
	 * @param SmtpConfigurationValidator|null $validator Optional validator for testing.
	 */
	public function __construct( ?SmtpConfigurationValidator $validator = null ) {
		$this->validator = $validator ?? new SmtpConfigurationValidator();
	}

	/**
	 * Returns the SMTP transport identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return self::ID;
	}

	/**
	 * Determines whether SMTP can run with the current configuration.
	 *
	 * @param Configuration $configuration Effective configuration.
	 * @return bool
	 */
	public function is_available( Configuration $configuration ): bool {
		return $this->validator->can_register( $configuration );
	}

	/**
	 * Registers SMTP with WordPress when its configuration is available.
	 *
	 * @param Configuration $configuration Effective configuration.
	 * @return void
	 */
	public function register( Configuration $configuration ): void {
		if ( $this->registered || ! $this->is_available( $configuration ) ) {
			return;
		}

		$this->configuration = $configuration;
		add_action( 'phpmailer_init', array( $this, 'configure_phpmailer' ) );
		$this->registered = true;
	}

	/**
	 * Switches WordPress's PHPMailer instance to SMTP delivery.
	 *
	 * SMTP connection settings are applied in the next implementation step.
	 *
	 * @param PHPMailer $phpmailer WordPress's PHPMailer instance.
	 * @return void
	 */
	public function configure_phpmailer( PHPMailer $phpmailer ): void {
		if ( null === $this->configuration ) {
			return;
		}

		$phpmailer->isSMTP();
	}
}
