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
			self::ENABLED   => false,
			self::TRANSPORT => 'none',
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
			self::ENABLED   => 'TENT_MAILER_ENABLED',
			self::TRANSPORT => 'TENT_MAILER_TRANSPORT',
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
		if ( self::ENABLED === $key ) {
			return self::normalize_boolean( $value );
		}

		if ( self::TRANSPORT === $key ) {
			$transport = strtolower( (string) $value );
			$transport = preg_replace( '/[^a-z0-9_-]/', '', $transport ) ?? '';

			return '' !== $transport ? $transport : self::defaults()[ self::TRANSPORT ];
		}

		return $value;
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
