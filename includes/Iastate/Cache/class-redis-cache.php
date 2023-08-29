<?php

/**
 * Simple Redis Cache
 */
class Redis_Cache implements Wpup_Cache {
	private $cache;

	public function __construct( $host = '127.0.0.1', $port = 6379 ) {
		$this->cache = new Redis();
		$this->cache->connect( $host, $port );
	}

	/**
	 * @inheritDoc
	 */
	function get( $key ) {
		return $this->cache->get( $key );
	}

	/**
	 * @inheritDoc
	 */
	function set( $key, $value, $expiration = 0 ) {
		$this->cache->setex( $key, $expiration, $value );
	}

	/**
	 * @inheritDoc
	 */
	function clear( $key ) {
		$this->cache->del( $key );
	}

	public function __destruct() {
		$this->cache->close();
	}
}
