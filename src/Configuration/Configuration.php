<?php
/**
 * Resolved plugin configuration.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer\Configuration;

/**
 * Immutable collection of resolved values and their origins.
 */
final class Configuration {

	public const SOURCE_CONSTANT = 'constant';
	public const SOURCE_NETWORK  = 'network';
	public const SOURCE_SITE     = 'site';
	public const SOURCE_DEFAULT  = 'default';

	/**
	 * Creates a resolved configuration.
	 *
	 * @param array<string, mixed>  $values  Resolved values by key.
	 * @param array<string, string> $sources Value sources by key.
	 * @param array<string, bool>   $locked  Lock states by key.
	 */
	public function __construct(
		private readonly array $values,
		private readonly array $sources,
		private readonly array $locked
	) {}

	/**
	 * Returns a resolved value.
	 *
	 * @param string $key      Configuration key.
	 * @param mixed  $fallback Value returned for an unknown key.
	 * @return mixed
	 */
	public function get( string $key, mixed $fallback = null ): mixed {
		return array_key_exists( $key, $this->values ) ? $this->values[ $key ] : $fallback;
	}

	/**
	 * Returns the source of a resolved value.
	 *
	 * @param string $key Configuration key.
	 * @return string|null
	 */
	public function source( string $key ): ?string {
		return $this->sources[ $key ] ?? null;
	}

	/**
	 * Determines whether a value is controlled above the site level.
	 *
	 * Constants and locked network settings are both considered locked.
	 *
	 * @param string $key Configuration key.
	 * @return bool
	 */
	public function is_locked( string $key ): bool {
		return $this->locked[ $key ] ?? false;
	}

	/**
	 * Returns all resolved values.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		return $this->values;
	}
}
