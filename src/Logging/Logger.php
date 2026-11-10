<?php
/**
 * Database-backed logging.
 *
 * @package TenT_Mailer
 */

namespace TenT\Mailer\Logging;

use Throwable;

/**
 * Writes structured plugin events to the shared log table.
 */
final class Logger {

	public const DEBUG    = 'debug';
	public const INFO     = 'info';
	public const WARNING  = 'warning';
	public const ERROR    = 'error';
	public const CRITICAL = 'critical';

	/**
	 * Supported log levels.
	 *
	 * @var string[]
	 */
	private const LEVELS = array(
		self::DEBUG,
		self::INFO,
		self::WARNING,
		self::ERROR,
		self::CRITICAL,
	);

	/**
	 * Keys whose values must never be persisted in log context.
	 *
	 * @var string[]
	 */
	private const SENSITIVE_KEYS = array(
		'api_key',
		'authorization',
		'client_secret',
		'encryption_key',
		'password',
		'passwd',
		'passphrase',
		'refresh_token',
		'smtp_password',
		'token',
		'access_token',
	);

	/**
	 * WordPress database connection.
	 *
	 * @var \wpdb
	 */
	private \wpdb $database;

	/**
	 * Creates the logger.
	 *
	 * @param \wpdb|null $database Optional database connection for testing.
	 */
	public function __construct( ?\wpdb $database = null ) {
		global $wpdb;

		$this->database = $database ?? $wpdb;
	}

	/**
	 * Writes an event to the log table.
	 *
	 * Messages must be safe for storage and must not contain credentials or raw
	 * protocol output. Sensitive values in the context array are redacted.
	 *
	 * @param string      $level          Event severity.
	 * @param string      $event          Stable machine-readable event name.
	 * @param string      $component      Component that produced the event.
	 * @param string      $message        Human-readable, sanitized message.
	 * @param array       $context        Additional structured details.
	 * @param string|null $correlation_id ID connecting related events.
	 * @param string|null $transport      Delivery transport, when applicable.
	 * @param string|null $status         Event outcome, when applicable.
	 * @param int|null    $duration_ms     Operation duration in milliseconds.
	 * @return bool Whether the event was written successfully.
	 */
	public function log(
		string $level,
		string $event,
		string $component,
		string $message,
		array $context = array(),
		?string $correlation_id = null,
		?string $transport = null,
		?string $status = null,
		?int $duration_ms = null
	): bool {
		if ( ! in_array( $level, self::LEVELS, true ) ) {
			return false;
		}

		try {
			$encoded_context = null;

			if ( array() !== $context ) {
				$encoded_context = wp_json_encode( $this->redact_context( $context ) );

				if ( false === $encoded_context ) {
					$encoded_context = null;
				}
			}

			$result = $this->database->insert(
				$this->database->base_prefix . '10t_mailer_logs',
				array(
					'blog_id'        => get_current_blog_id(),
					'created_at'     => current_time( 'mysql', true ),
					'level'          => $level,
					'event'          => $this->normalize_identifier( $event, 100 ),
					'component'      => $this->normalize_identifier( $component, 64 ),
					'message'        => sanitize_textarea_field( $message ),
					'correlation_id' => $this->normalize_optional_value( $correlation_id, 64 ),
					'transport'      => $this->normalize_optional_value( $transport, 32 ),
					'status'         => $this->normalize_optional_value( $status, 32 ),
					'duration_ms'    => null !== $duration_ms && 0 <= $duration_ms ? $duration_ms : null,
					'context'        => $encoded_context,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
			);

			return false !== $result;
		} catch ( Throwable ) {
			return false;
		}
	}

	/**
	 * Redacts sensitive values and normalizes unsupported context values.
	 *
	 * @param array $context Context to redact.
	 * @return array
	 */
	private function redact_context( array $context ): array {
		$redacted = array();

		foreach ( $context as $key => $value ) {
			$normalized_key = strtolower( str_replace( array( '-', ' ' ), '_', (string) $key ) );

			if ( $this->is_sensitive_key( $normalized_key ) ) {
				$redacted[ $key ] = '[REDACTED]';
			} elseif ( is_array( $value ) ) {
				$redacted[ $key ] = $this->redact_context( $value );
			} elseif ( is_scalar( $value ) || null === $value ) {
				$redacted[ $key ] = $value;
			} elseif ( is_object( $value ) ) {
				$redacted[ $key ] = '[object ' . get_class( $value ) . ']';
			} else {
				$redacted[ $key ] = '[' . gettype( $value ) . ']';
			}
		}

		return $redacted;
	}

	/**
	 * Determines whether a context key can identify a secret value.
	 *
	 * @param string $key Normalized context key.
	 * @return bool
	 */
	private function is_sensitive_key( string $key ): bool {
		if ( in_array( $key, self::SENSITIVE_KEYS, true ) ) {
			return true;
		}

		$compact_key = str_replace( '_', '', $key );

		foreach ( self::SENSITIVE_KEYS as $sensitive_key ) {
			if ( $compact_key === str_replace( '_', '', $sensitive_key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalizes a machine-readable identifier.
	 *
	 * @param string $value  Identifier to normalize.
	 * @param int    $length Maximum length.
	 * @return string
	 */
	private function normalize_identifier( string $value, int $length ): string {
		$value = strtolower( preg_replace( '/[^a-zA-Z0-9._-]/', '', $value ) ?? '' );

		return substr( $value, 0, $length );
	}

	/**
	 * Normalizes an optional database value.
	 *
	 * @param string|null $value  Value to normalize.
	 * @param int         $length Maximum length.
	 * @return string|null
	 */
	private function normalize_optional_value( ?string $value, int $length ): ?string {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return substr( sanitize_text_field( $value ), 0, $length );
	}
}
