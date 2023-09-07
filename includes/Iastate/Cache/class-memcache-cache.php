<?php

defined( 'WP_UPDATE_ROOT_PATH' ) || exit;

if (!class_exists('Memcache')){
	return;
}
/**
 * Simple Redis Cache
 */
class Memcache_Cache implements Wpup_Cache {
	/**
	 * Holds the error messages.
	 *
	 * @var array
	 */
	public $errors = [];
	/**
	 * @var Memcache
	 */
	private $cache;
	/**
	 * Track if Memcache is available.
	 *
	 * @var bool
	 */
	private $connected = false;

	public function __construct() {
		$this->connect();
	}

	public function connect() {
		$parameters = [
			'host'    => '127.0.0.1',
			'port'    => 11211,
			'timeout' => 1,
		];

		$settings = [
			'host',
			'port',
			'timeout',
		];

		foreach ( $settings as $setting ) {
			$constant = sprintf( 'WP_UPDATE_MEMCACHE_%s', strtoupper( $setting ) );

			if ( defined( $constant ) ) {
				$parameters[ $setting ] = constant( $constant );
			}
		}

		$this->cache = new Memcache;
		$this->cache->connect( $parameters['host'], $parameters['port'], $parameters['timeout'] );
		$this->connected = true;
	}

	/**
	 * @inheritDoc
	 */
	function get( $key ) {
		if ( ! $this->status() ) {
			return null;
		}
		$san_key     = $this->sanitize_key_part( $key );
		$derived_key = $this->fast_build_key( $san_key );

		return $this->cache->get( $derived_key );
	}

	/**
	 * Is Redis available?
	 *
	 * @return bool
	 */
	public function status() {
		return (bool) $this->connected;
	}

	/**
	 * Replaces the set group separator by another one
	 *
	 * @param string $part The string to sanitize.
	 *
	 * @return  string        Sanitized string.
	 */
	protected function sanitize_key_part( $part ) {
		return str_replace( ':', '-', $part );
	}

	/**
	 * Builds a key for the cached object using the prefix and key.
	 *
	 * @param string $key The key under which to store the value, pre-sanitized.
	 *
	 * @return  string
	 */
	public function fast_build_key( $key ) {
		$salt           = defined( 'WP_UPDATE_MEMCACHE_PREFIX' ) ? trim( WP_UPDATE_MEMCACHE_PREFIX ) : '';
		$max_key_length = 250;

		return substr( "{$salt}:{$key}", 0, $max_key_length );
	}

	/**
	 * @inheritDoc
	 */
	function set( $key, $value, $expiration = 0 ) {
		if ( ! $this->status() ) {
			return;
		}
		$san_key     = $this->sanitize_key_part( $key );
		$derived_key = $this->fast_build_key( $san_key );
		$expiration  = $this->validate_expiration( $expiration );

		$result = $this->cache->replace( $derived_key, $value, null, $expiration );

		if ( false === $result ) {
			$this->cache->set( $derived_key, $value, null, $expiration );
		}

	}

	/**
	 * Wrapper to validate the cache keys expiration value
	 *
	 * @param mixed $expiration Incoming expiration value (whatever it is).
	 */
	protected function validate_expiration( $expiration ) {
		$expiration = is_int( $expiration ) || ctype_digit( (string) $expiration ) ? (int) $expiration : 0;
		$max        = 2592000; //Max of 30 days in seconds

		if ( $expiration === 0 || $expiration > $max ) {
			$expiration = $max;
		}

		return $expiration;
	}

	/**
	 * @inheritDoc
	 */
	function clear( $key ) {
		if ( ! $this->status() ) {
			return;
		}
		$san_key     = $this->sanitize_key_part( $key );
		$derived_key = $this->fast_build_key( $san_key );

		$this->cache->delete( $derived_key );
	}

	public function __destruct() {
		$this->cache->close();
	}

	/**
	 * Handle the redis failure gracefully or throw an exception.
	 *
	 * @param Exception $exception Exception thrown.
	 *
	 * @return void
	 * @throws Exception If `fail_gracefully` flag is set to a falsy value.
	 */
	protected function handle_exception( $exception ) {
		$this->connected = false;

		$this->errors[] = $exception->getMessage();
	}
}
