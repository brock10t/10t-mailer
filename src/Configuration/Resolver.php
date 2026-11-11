<?php
/**
 * Plugin configuration resolution.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer\Configuration;

/**
 * Resolves configuration from constants, network settings, site settings,
 * and plugin defaults.
 */
final class Resolver {

	/**
	 * Resolves the effective plugin configuration.
	 *
	 * Precedence is: constants, locked network values, site values, unlocked
	 * network defaults, and plugin defaults.
	 *
	 * @return Configuration
	 */
	public function resolve(): Configuration {
		$site_values    = $this->read_array_option( Keys::SITE_OPTION, false );
		$network_values = array();
		$network_locks  = array();

		if ( is_multisite() ) {
			$network_values = $this->read_array_option( Keys::NETWORK_OPTION, true );
			$network_locks  = $this->read_network_locks();
		}

		$values  = array();
		$sources = array();
		$locked  = array();

		foreach ( Keys::all() as $key ) {
			$constant_name = Keys::constant_name( $key );

			if ( null !== $constant_name && defined( $constant_name ) ) {
				$values[ $key ]  = Keys::normalize( $key, constant( $constant_name ) );
				$sources[ $key ] = Configuration::SOURCE_CONSTANT;
				$locked[ $key ]  = true;
				continue;
			}

			if ( isset( $network_locks[ $key ] ) && array_key_exists( $key, $network_values ) ) {
				$values[ $key ]  = Keys::normalize( $key, $network_values[ $key ] );
				$sources[ $key ] = Configuration::SOURCE_NETWORK;
				$locked[ $key ]  = true;
				continue;
			}

			if ( array_key_exists( $key, $site_values ) ) {
				$values[ $key ]  = Keys::normalize( $key, $site_values[ $key ] );
				$sources[ $key ] = Configuration::SOURCE_SITE;
				$locked[ $key ]  = false;
				continue;
			}

			if ( array_key_exists( $key, $network_values ) ) {
				$values[ $key ]  = Keys::normalize( $key, $network_values[ $key ] );
				$sources[ $key ] = Configuration::SOURCE_NETWORK;
				$locked[ $key ]  = false;
				continue;
			}

			$values[ $key ]  = Keys::defaults()[ $key ];
			$sources[ $key ] = Configuration::SOURCE_DEFAULT;
			$locked[ $key ]  = false;
		}

		return new Configuration( $values, $sources, $locked );
	}

	/**
	 * Reads an option that is expected to contain an array.
	 *
	 * @param string $option_name Option name.
	 * @param bool   $network     Whether to read a network option.
	 * @return array
	 */
	private function read_array_option( string $option_name, bool $network ): array {
		$value = $network
			? get_site_option( $option_name, array() )
			: get_option( $option_name, array() );

		return is_array( $value ) ? $value : array();
	}

	/**
	 * Reads locked network keys as a lookup map.
	 *
	 * Both a list of keys and an associative key-to-boolean map are accepted.
	 *
	 * @return array<string, bool>
	 */
	private function read_network_locks(): array {
		$stored = $this->read_array_option( Keys::NETWORK_LOCKS_OPTION, true );
		$locks  = array();

		foreach ( $stored as $key => $value ) {
			if ( is_int( $key ) && is_string( $value ) ) {
				$locks[ $value ] = true;
			} elseif ( is_string( $key ) && in_array( $value, array( true, 1, '1' ), true ) ) {
				$locks[ $key ] = true;
			}
		}

		return $locks;
	}
}
