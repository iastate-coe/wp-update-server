<?php

/**
 * Simple Redis Cache
 */
class Redis_Cache implements Wpup_Cache {
	private $cache;

	/**
	 * @throws RedisException
	 */
	public function __construct( $host = '127.0.0.1', $port = 6379 ) {
		$this->cache = new Redis();
		$this->cache->connect( $host, $port );
	}

	/**
	 * @inheritDoc
	 * @throws RedisException
	 */
	function get( $key ) {
		return $this->cache->get( $key );
	}

	/**
	 * @inheritDoc
	 * @throws RedisException
	 */
	function set( $key, $value, $expiration = 0 ) {
		$this->cache->setex( $key, $expiration, $value );
	}

	/**
	 * @inheritDoc
	 * @throws RedisException
	 */
	function clear( $key ) {
		$this->cache->del( $key );
	}

	/**
	 * @throws RedisException
	 */
	public function __destruct() {
		$this->cache->close();
	}
}
