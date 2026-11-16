<?php
/**
 * Configuration key definitions.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer\Configuration;

/**
 * Defines supported configuration keys and their metadata.
 */
final class Keys {

	public const ENABLED   = 'enabled';
	public const TRANSPORT = 'transport';

	public const SMTP_HOST               = 'smtp_host';
	public const SMTP_PORT               = 'smtp_port';
	public const SMTP_ENCRYPTION         = 'smtp_encryption';
	public const SMTP_AUTHENTICATION     = 'smtp_authentication';
	public const SMTP_USERNAME           = 'smtp_username';
	public const SMTP_PASSWORD           = 'smtp_password';
	public const SMTP_CONNECTION_TIMEOUT = 'smtp_connection_timeout';
	public const SMTP_FROM_EMAIL         = 'smtp_from_email';
	public const SMTP_FROM_NAME          = 'smtp_from_name';

	public const ENCRYPTION_NONE         = 'none';
	public const ENCRYPTION_STARTTLS     = 'starttls';
	public const ENCRYPTION_IMPLICIT_TLS = 'implicit_tls';

	public const SITE_OPTION          = 'tent_mailer_settings';
	public const NETWORK_OPTION       = 'tent_mailer_network_settings';
	public const NETWORK_LOCKS_OPTION = 'tent_mailer_network_locked_keys';

	/**
	 * Returns every supported key.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array_keys( self::defaults() );
	}

	/**
	 * Returns plugin defaults by key.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			self::ENABLED                 => false,
			self::TRANSPORT               => 'none',
			self::SMTP_HOST               => '',
			self::SMTP_PORT               => 587,
			self::SMTP_ENCRYPTION         => self::ENCRYPTION_STARTTLS,
			self::SMTP_AUTHENTICATION     => true,
			self::SMTP_USERNAME           => '',
			self::SMTP_PASSWORD           => '',
			self::SMTP_CONNECTION_TIMEOUT => 30,
			self::SMTP_FROM_EMAIL         => '',
			self::SMTP_FROM_NAME          => '',
		);
	}

	/**
	 * Returns the wp-config.php constant associated with a key.
	 *
	 * @param string $key Configuration key.
	 * @return string|null
	 */
	public static function constant_name( string $key ): ?string {
		$constants = array(
			self::ENABLED                 => 'TENT_MAILER_ENABLED',
			self::TRANSPORT               => 'TENT_MAILER_TRANSPORT',
			self::SMTP_HOST               => 'TENT_MAILER_SMTP_HOST',
			self::SMTP_PORT               => 'TENT_MAILER_SMTP_PORT',
			self::SMTP_ENCRYPTION         => 'TENT_MAILER_SMTP_ENCRYPTION',
			self::SMTP_AUTHENTICATION     => 'TENT_MAILER_SMTP_AUTHENTICATION',
			self::SMTP_USERNAME           => 'TENT_MAILER_SMTP_USERNAME',
			self::SMTP_PASSWORD           => 'TENT_MAILER_SMTP_PASSWORD',
			self::SMTP_CONNECTION_TIMEOUT => 'TENT_MAILER_SMTP_CONNECTION_TIMEOUT',
			self::SMTP_FROM_EMAIL         => 'TENT_MAILER_SMTP_FROM_EMAIL',
			self::SMTP_FROM_NAME          => 'TENT_MAILER_SMTP_FROM_NAME',
		);

		return $constants[ $key ] ?? null;
	}

	/**
	 * Normalizes a resolved value for its configuration key.
	 *
	 * @param string $key   Configuration key.
	 * @param mixed  $value Raw configuration value.
	 * @return mixed
	 */
	public static function normalize( string $key, mixed $value ): mixed {
		if ( in_array( $key, array( self::ENABLED, self::SMTP_AUTHENTICATION ), true ) ) {
			return self::normalize_boolean( $value );
		}

		if ( self::TRANSPORT === $key ) {
			$transport = strtolower( self::normalize_string( $value ) );
			$transport = preg_replace( '/[^a-z0-9_-]/', '', $transport ) ?? '';

			return '' !== $transport ? $transport : self::defaults()[ self::TRANSPORT ];
		}

		if ( self::SMTP_PORT === $key ) {
			return self::normalize_integer( $value, 1, 65535, self::defaults()[ self::SMTP_PORT ] );
		}

		if ( self::SMTP_CONNECTION_TIMEOUT === $key ) {
			return self::normalize_integer(
				$value,
				1,
				300,
				self::defaults()[ self::SMTP_CONNECTION_TIMEOUT ]
			);
		}

		if ( self::SMTP_ENCRYPTION === $key ) {
			$encryption = strtolower( self::normalize_string( $value ) );

			return in_array(
				$encryption,
				array( self::ENCRYPTION_NONE, self::ENCRYPTION_STARTTLS, self::ENCRYPTION_IMPLICIT_TLS ),
				true
			)
				? $encryption
				: self::defaults()[ self::SMTP_ENCRYPTION ];
		}

		if ( self::SMTP_PASSWORD === $key ) {
			return self::normalize_string( $value, false );
		}

		if (
			in_array(
				$key,
				array( self::SMTP_HOST, self::SMTP_USERNAME, self::SMTP_FROM_EMAIL, self::SMTP_FROM_NAME ),
				true
			)
		) {
			return self::normalize_string( $value );
		}

		return $value;
	}

	/**
	 * Normalizes an integer within an allowed range.
	 *
	 * @param mixed $value    Raw value.
	 * @param int   $minimum  Minimum accepted value.
	 * @param int   $maximum  Maximum accepted value.
	 * @param int   $fallback Value returned when invalid.
	 * @return int
	 */
	private static function normalize_integer( mixed $value, int $minimum, int $maximum, int $fallback ): int {
		if ( is_int( $value ) ) {
			$integer = $value;
		} elseif ( is_string( $value ) && ctype_digit( trim( $value ) ) ) {
			$integer = (int) trim( $value );
		} else {
			return $fallback;
		}

		return $integer >= $minimum && $integer <= $maximum ? $integer : $fallback;
	}

	/**
	 * Normalizes scalar and stringable values to strings.
	 *
	 * @param mixed $value Raw value.
	 * @param bool  $trim  Whether to trim surrounding whitespace.
	 * @return string
	 */
	private static function normalize_string( mixed $value, bool $trim = true ): string {
		if ( ! is_scalar( $value ) && ! $value instanceof \Stringable ) {
			return '';
		}

		$value = (string) $value;

		return $trim ? trim( $value ) : $value;
	}

	/**
	 * Normalizes common boolean representations.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function normalize_boolean( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_int( $value ) ) {
			return 1 === $value;
		}

		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'yes', 'on' ), true );
		}

		return false;
	}
}
