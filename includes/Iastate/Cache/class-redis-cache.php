<?php

defined( 'WP_UPDATE_ROOT_PATH' ) || exit;

if (!class_exists('Redis')){
	return;
}

/**
 * Simple Redis Cache
 */
class Redis_Cache implements Wpup_Cache {
	/**
	 * Holds the non-Redis objects.
	 *
	 * @var array
	 */
	public $cache = [];
	/**
	 * Holds the error messages.
	 *
	 * @var array
	 */
	public $errors = [];
	/**
	 * Holds the diagnostics values.
	 *
	 * @var array
	 */
	public $diagnostics = null;
	/**
	 * The Redis client.
	 *
	 * @var mixed
	 */
	private $redis;
	/**
	 * Track if Redis is available.
	 *
	 * @var bool
	 */
	private $connected = false;

	/**
	 * @throws RedisException
	 */
	public function __construct() {
		$this->connect();
	}

	/**
	 * @throws RedisException
	 */
	public function connect() {
		$parameters = [
			'scheme'         => 'tcp',
			'host'           => '127.0.0.1',
			'port'           => 6379,
			'database'       => 0,
			'timeout'        => 1,
			'read_timeout'   => 1,
			'retry_interval' => null,
			'persistent'     => false,
		];

		$settings = [
			'scheme',
			'host',
			'port',
			'path',
			'password',
			'database',
			'timeout',
			'read_timeout',
			'retry_interval',
		];

		foreach ( $settings as $setting ) {
			$constant = sprintf( 'WP_UPDATE_REDIS_%s', strtoupper( $setting ) );

			if ( defined( $constant ) ) {
				$parameters[ $setting ] = constant( $constant );
			}
		}

		if ( isset( $parameters['password'] ) && $parameters['password'] === '' ) {
			unset( $parameters['password'] );
		}

		$version                     = phpversion( 'redis' );
		$this->diagnostics['client'] = sprintf( 'PhpRedis (v%s)', $version );

		$this->redis = new Redis();

		$args = [
			'host'           => $parameters['host'],
			'port'           => $parameters['port'],
			'timeout'        => $parameters['timeout'],
			'',
			'retry_interval' => (int) $parameters['retry_interval'],
		];

		if ( version_compare( $version, '3.1.3', '>=' ) ) {
			$args['read_timeout'] = $parameters['read_timeout'];
		}

		if ( strcasecmp( 'tls', $parameters['scheme'] ) === 0 ) {
			$args['host'] = sprintf(
				'%s://%s',
				$parameters['scheme'],
				str_replace( 'tls://', '', $parameters['host'] )
			);

			if ( version_compare( $version, '5.3.0', '>=' ) && defined( 'WP_UPDATE_REDIS_SSL_CONTEXT' ) && ! empty( WP_UPDATE_REDIS_SSL_CONTEXT ) ) {
				$args['others']['stream'] = WP_UPDATE_REDIS_SSL_CONTEXT;
			}
		}

		if ( strcasecmp( 'unix', $parameters['scheme'] ) === 0 ) {
			$args['host'] = $parameters['path'];
			$args['port'] = - 1;
		}

		call_user_func_array( [ $this->redis, 'connect' ], array_values( $args ) );

		if ( isset( $parameters['password'] ) ) {
			$args['password'] = $parameters['password'];
			$this->redis->auth( $parameters['password'] );
		}

		if ( isset( $parameters['database'] ) ) {
			if ( ctype_digit( (string) $parameters['database'] ) ) {
				$parameters['database'] = (int) $parameters['database'];
			}

			$args['database'] = $parameters['database'];

			if ( $parameters['database'] ) {
				$this->redis->select( $parameters['database'] );
			}
		}
		$this->diagnostics += $args;
		$this->connected = true;
	}

	/**
	 * @inheritDoc
	 */
	public function clear( $key ) {
		$san_key     = $this->sanitize_key_part( $key );
		$derived_key = $this->fast_build_key( $san_key);

		if ( array_key_exists( $derived_key, $this->cache ) ) {
			unset( $this->cache[ $derived_key ] );
		}

		if ( $this->redis_status() ) {
			try {
				$this->parse_redis_response( $this->redis->del( $derived_key ) );
			} catch ( Exception $exception ) {
				$this->handle_exception( $exception );

				return;
			}
		}

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
		$salt = defined( 'WP_UPDATE_REDIS_PREFIX' ) ? trim( WP_UPDATE_REDIS_PREFIX ) : '';

		return "{$salt}:{$key}";
	}

	/**
	 * Is Redis available?
	 *
	 * @return bool
	 */
	public function redis_status() {
		return (bool) $this->connected;
	}

	/**
	 * Convert Redis responses into something meaningful
	 *
	 * @param mixed $response Response sent from the redis instance.
	 *
	 * @return mixed
	 */
	protected function parse_redis_response( $response ) {
		if ( is_bool( $response ) ) {
			return $response;
		}

		if ( is_numeric( $response ) ) {
			return $response;
		}

		if ( is_object( $response ) && method_exists( $response, 'getPayload' ) ) {
			return $response->getPayload() === 'OK';
		}

		return false;
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

	/**
	 *
	 * @return bool True on success, false on failure.
	 */
	public function flush() {
		$results     = [];
		$this->cache = [];

		if ( $this->redis_status() ) {
			try {
				$results[] = $this->parse_redis_response( $this->redis->flushdb() );
			} catch ( Exception $exception ) {
				$this->handle_exception( $exception );

				return false;
			}
		}

		if ( empty( $results ) ) {
			return false;
		}

		foreach ( $results as $result ) {
			if ( ! $result ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @throws RedisException
	 */
	public function __destruct() {
		$this->redis->close();
	}

	/**
	 * @inheritDoc
	 */
	public function set( $key, $value, $expiration = 0 ) {
		$result      = true;
		$san_key     = $this->sanitize_key_part( $key );
		$derived_key = $this->fast_build_key( $san_key);

		if ( $this->redis_status() ) {
			$expiration = $this->validate_expiration( $expiration );

			try {
				if ( $expiration ) {
					$result = $this->parse_redis_response(
						$this->redis->setex( $derived_key, $expiration, $this->maybe_serialize( $value ) )
					);
				} else {
					$result = $this->parse_redis_response(
						$this->redis->set( $derived_key, $this->maybe_serialize( $value ) )
					);
				}
			} catch ( Exception $exception ) {
				$this->handle_exception( $exception );

				return false;
			}

		}

		// If the set was successful, or we didn't go to redis.
		if ( $result ) {
			$this->add_to_internal_cache( $derived_key, $value );
		}

		return $result;
	}

	/**
	 * Wrapper to validate the cache keys expiration value
	 *
	 * @param mixed $expiration Incoming expiration value (whatever it is).
	 */
	protected function validate_expiration( $expiration ) {
		$expiration = is_int( $expiration ) || ctype_digit( (string) $expiration ) ? (int) $expiration : 0;

		if ( defined( 'WP_UPDATE_REDIS_MAXTTL' ) ) {
			$max = (int) WP_UPDATE_REDIS_MAXTTL;

			if ( $expiration === 0 || $expiration > $max ) {
				$expiration = $max;
			}
		}

		return $expiration;
	}

	/**
	 * Serialize data, if needed.
	 *
	 * @param mixed $data Data that might be serialized.
	 *
	 * @return mixed       A scalar data
	 */
	protected function maybe_serialize( $data ) {
		if ( is_object( $data ) ) {
			$data = clone $data;
		}

		if ( defined( 'WP_UPDATE_REDIS_IGBINARY' ) && WP_UPDATE_REDIS_IGBINARY && function_exists( 'igbinary_serialize' ) ) {
			return igbinary_serialize( $data );
		}

		if ( is_array( $data ) || is_object( $data ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			return serialize( $data );
		}

		if ( $this->is_serialized( $data, false ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			return serialize( $data );
		}

		return $data;
	}

	/**
	 * Check value to find if it was serialized.
	 *
	 * If $data is not an string, then returned value will always be false.
	 * Serialized data is always a string.
	 *
	 * @param string $data Value to check to see if was serialized.
	 * @param bool $strict Optional. Whether to be strict about the end of the string. Default true.
	 *
	 * @return bool           False if not serialized and true if it was.
	 */
	protected function is_serialized( $data, $strict = true ) {
		// if it isn't a string, it isn't serialized.
		if ( ! is_string( $data ) ) {
			return false;
		}

		$data = trim( $data );

		if ( 'N;' === $data ) {
			return true;
		}

		if ( strlen( $data ) < 4 ) {
			return false;
		}

		if ( ':' !== $data[1] ) {
			return false;
		}

		if ( $strict ) {
			$lastc = substr( $data, - 1 );

			if ( ';' !== $lastc && '}' !== $lastc ) {
				return false;
			}
		} else {
			$semicolon = strpos( $data, ';' );
			$brace     = strpos( $data, '}' );

			// Either ; or } must exist.
			if ( false === $semicolon && false === $brace ) {
				return false;
			}

			// But neither must be in the first X characters.
			if ( false !== $semicolon && $semicolon < 3 ) {
				return false;
			}

			if ( false !== $brace && $brace < 4 ) {
				return false;
			}
		}
		$token = $data[0];

		switch ( $token ) {
			case 's':
				if ( $strict ) {
					if ( '"' !== substr( $data, - 2, 1 ) ) {
						return false;
					}
				} elseif ( false === strpos( $data, '"' ) ) {
					return false;
				}
			// Or else fall through.
			// No break!
			case 'a':
			case 'O':
				return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
			case 'b':
			case 'i':
			case 'd':
				$end = $strict ? '$' : '';

				return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
		}

		return false;
	}

	/**
	 * Simple wrapper for saving object to the internal cache.
	 *
	 * @param string $derived_key Key to save value under.
	 * @param mixed $value Object value.
	 */
	public function add_to_internal_cache( $derived_key, $value ) {
		if ( is_object( $value ) ) {
			$value = clone $value;
		}

		$this->cache[ $derived_key ] = $value;
	}
	/**
	 * Returns various information about the object cache.
	 *
	 * @return object
	 */
	public function info() {
		$bytes = array_map(
			function ( $keys ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				return strlen( serialize( $keys ) );
			},
			$this->cache
		);

		return (object) [
			'bytes'  => array_sum( $bytes ),
			'errors' => empty( $this->errors ) ? null : $this->errors,
			'meta'   => [
				'Client' => $this->diagnostics['client'] ?? 'Unknown',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function get( $key ) {
		$san_key     = $this->sanitize_key_part( $key );
		$derived_key = $this->fast_build_key( $san_key );

		if ( array_key_exists( $derived_key, $this->cache ) ) {
			$value = $this->get_from_internal_cache( $derived_key );

			return $value;
		} elseif ( ! $this->redis_status() ) {
			return null;
		}

		try {
			$result = $this->redis->get( $derived_key );
		} catch ( Exception $exception ) {
			$this->handle_exception( $exception );

			return null;
		}

		if ( $result === null || $result === false ) {
			return null;
		} else {
			$value = $this->maybe_unserialize( $result );
		}

		$this->add_to_internal_cache( $derived_key, $value );

		return $value;
	}

	/**
	 * Get a value specifically from the internal, run-time cache, not Redis.
	 *
	 * @param int|string $derived_key Key value.
	 *
	 * @return  bool|mixed              Value on success; false on failure.
	 */
	public function get_from_internal_cache( $derived_key ) {
		if ( ! array_key_exists( $derived_key, $this->cache ) ) {
			return false;
		}

		if ( is_object( $this->cache[ $derived_key ] ) ) {
			return clone $this->cache[ $derived_key ];
		}

		return $this->cache[ $derived_key ];
	}

	/**
	 * Unserialize value only if it was serialized.
	 *
	 * @param string $original Maybe unserialized original, if is needed.
	 *
	 * @return mixed            Unserialized data can be any type.
	 */
	protected function maybe_unserialize( $original ) {
		if ( defined( 'WP_UPDATE_REDIS_IGBINARY' ) && WP_UPDATE_REDIS_IGBINARY && function_exists( 'igbinary_unserialize' ) ) {
			return igbinary_unserialize( $original );
		}

		// Don't attempt to unserialize data that wasn't serialized going in.
		if ( $this->is_serialized( $original ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			$value = @unserialize( $original );

			return is_object( $value ) ? clone $value : $value;
		}

		return $original;
	}

}
