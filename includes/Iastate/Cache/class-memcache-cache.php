<?php

/**
 * Simple Redis Cache
 */
class Memcache_Cache implements Wpup_Cache {
	private $cache;

	public function __construct( $host = '127.0.0.1', $port = 6379 ) {
		$this->cache = new Memcache();
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
		$this->cache->set( $key, $value, null, time() + $expiration );
	}

	/**
	 * @inheritDoc
	 */
	function clear( $key ) {
		$this->cache->delete( $key );
	}

	public function __destruct() {
		$this->cache->close();
	}
}
