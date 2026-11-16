<?php
/**
 * SMTP configuration validation.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer\Configuration;

/**
 * Determines whether resolved configuration is safe and complete for SMTP.
 */
final class SmtpConfigurationValidator {

	public const ERROR_HOST_REQUIRED      = 'smtp_host_required';
	public const ERROR_HOST_INVALID       = 'smtp_host_invalid';
	public const ERROR_PORT_INVALID       = 'smtp_port_invalid';
	public const ERROR_ENCRYPTION_INVALID = 'smtp_encryption_invalid';
	public const ERROR_AUTHENTICATION_INVALID = 'smtp_authentication_invalid';
	public const ERROR_USERNAME_REQUIRED  = 'smtp_username_required';
	public const ERROR_PASSWORD_REQUIRED  = 'smtp_password_required';
	public const ERROR_TIMEOUT_INVALID    = 'smtp_connection_timeout_invalid';
	public const ERROR_FROM_EMAIL_INVALID = 'smtp_from_email_invalid';
	public const ERROR_FROM_NAME_INVALID  = 'smtp_from_name_invalid';

	/**
	 * Determines whether SMTP may register for the current request.
	 *
	 * @param Configuration $configuration Effective configuration.
	 * @return bool
	 */
	public function can_register( Configuration $configuration ): bool {
		return true === $configuration->get( Keys::ENABLED )
			&& 'smtp' === $configuration->get( Keys::TRANSPORT )
			&& $this->is_valid( $configuration );
	}

	/**
	 * Determines whether the SMTP-specific values are valid and complete.
	 *
	 * @param Configuration $configuration Effective configuration.
	 * @return bool
	 */
	public function is_valid( Configuration $configuration ): bool {
		return array() === $this->errors( $configuration );
	}

	/**
	 * Returns stable, non-sensitive validation error codes.
	 *
	 * @param Configuration $configuration Effective configuration.
	 * @return string[]
	 */
	public function errors( Configuration $configuration ): array {
		$errors = array();
		$host   = $configuration->get( Keys::SMTP_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			$errors[] = self::ERROR_HOST_REQUIRED;
		} elseif ( ! $this->is_valid_host( $host ) ) {
			$errors[] = self::ERROR_HOST_INVALID;
		}

		$port = $configuration->get( Keys::SMTP_PORT );

		if ( ! is_int( $port ) || 1 > $port || 65535 < $port ) {
			$errors[] = self::ERROR_PORT_INVALID;
		}

		if (
			! in_array(
				$configuration->get( Keys::SMTP_ENCRYPTION ),
				array( Keys::ENCRYPTION_NONE, Keys::ENCRYPTION_STARTTLS, Keys::ENCRYPTION_IMPLICIT_TLS ),
				true
			)
		) {
			$errors[] = self::ERROR_ENCRYPTION_INVALID;
		}

		$authentication = $configuration->get( Keys::SMTP_AUTHENTICATION );

		if ( ! is_bool( $authentication ) ) {
			$errors[] = self::ERROR_AUTHENTICATION_INVALID;
		} elseif ( true === $authentication ) {
			$username = $configuration->get( Keys::SMTP_USERNAME );
			$password = $configuration->get( Keys::SMTP_PASSWORD );

			if ( ! is_string( $username ) || '' === $username ) {
				$errors[] = self::ERROR_USERNAME_REQUIRED;
			}

			if ( ! is_string( $password ) || '' === $password ) {
				$errors[] = self::ERROR_PASSWORD_REQUIRED;
			}
		}

		$timeout = $configuration->get( Keys::SMTP_CONNECTION_TIMEOUT );

		if ( ! is_int( $timeout ) || 1 > $timeout || 300 < $timeout ) {
			$errors[] = self::ERROR_TIMEOUT_INVALID;
		}

		$from_email = $configuration->get( Keys::SMTP_FROM_EMAIL );

		if ( ! is_string( $from_email ) || ( '' !== $from_email && ! is_email( $from_email ) ) ) {
			$errors[] = self::ERROR_FROM_EMAIL_INVALID;
		}

		$from_name = $configuration->get( Keys::SMTP_FROM_NAME );

		if (
			! is_string( $from_name )
			|| 255 < strlen( $from_name )
			|| false !== strpos( $from_name, "\r" )
			|| false !== strpos( $from_name, "\n" )
		) {
			$errors[] = self::ERROR_FROM_NAME_INVALID;
		}

		return $errors;
	}

	/**
	 * Determines whether a value is a hostname or IP address without a scheme,
	 * path, or port.
	 *
	 * @param string $host SMTP host.
	 * @return bool
	 */
	private function is_valid_host( string $host ): bool {
		if (
			253 < strlen( $host )
			|| false !== strpos( $host, '://' )
			|| false !== strpos( $host, '/' )
			|| preg_match( '/\s/', $host )
		) {
			return false;
		}

		$ip_address = $host;

		if ( str_starts_with( $host, '[' ) && str_ends_with( $host, ']' ) ) {
			$ip_address = substr( $host, 1, -1 );
		}

		if ( false !== filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
			return true;
		}

		return false !== filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME );
	}
}
