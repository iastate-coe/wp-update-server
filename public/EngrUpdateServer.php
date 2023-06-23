<?php

class Engr_UpdateServer extends Wpup_UpdateServer {
	private $authenticationKey;

	public function __construct( $serverUrl = null, $serverDirectory = null, $authenticationKey = null ) {
		parent::__construct( $serverUrl, $serverDirectory );
		$this->authenticationKey = $authenticationKey;
		$this->logDirectory      = WP_UPDATE_ROOT_PATH . '/logs';
		$this->cache             = new Wpup_FileCache( WP_UPDATE_ROOT_PATH . '/cache' );
	}

	public static function isSsl() {
		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
			return true;
		}

		return parent::isSsl();
	}

	protected function initRequest( $query = null, $headers = null ) {
		$request = parent::initRequest( $query, $headers );

		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$request->clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		//Load the license, if any.
		$authKey = null;
		if ( ! empty( $request->headers->get( 'Authorization' ) ) ) {
			$authKey = urldecode( str_replace( 'Basic ', '', $request->headers->get( 'Authorization' ) ) );
		}

		$request->authKey = $authKey;

		return $request;
	}

	protected function filterMetadata( $meta, $request ) {
		$meta = parent::filterMetadata( $meta, $request );

		//Include license information in the update metadata. This saves an HTTP request
		//or two since the plugin doesn't need to explicitly fetch license details.
		$authKey = $request->authKey;
		if ( $authKey !== null ) {
			$meta['auth_key'] = $authKey;
		}

		//Only include the download URL if the license is valid.
		if ( $this->isAuthenticationKeyValid( $authKey ) ) {
			//Append the license key or to the download URL.
			$args                 = array( 'uid' => $this->generateUniqueQueryArg( $request ) );
			$meta['download_url'] = self::addQueryArg( $args, $meta['download_url'] );
		} else {
			//No license = no download link.
			unset( $meta['download_url'] );
		}

		return $meta;
	}

	/**
	 * @param string $string
	 */
	private function isAuthenticationKeyValid( $string ): bool {
		return $this->authenticationKey === $string;
	}

	/**
	 * @param Wpup_Request $request
	 */
	private function isHashValid( $request ): bool {
		$parts    = array(
			'action'  => (string) $request->action,
			'slug'    => (string) $request->slug,
			'version' => (string) $request->wpVersion,
			'url'     => (string) $request->wpSiteUrl,
		);
		$sentHash = base64_decode( urldecode( $request->param( 'uid' ) ) );
		$hash     = hash( WP_UPDATE_HASH_ALGO, implode( ';', $parts ) );

		return $hash === $sentHash;
	}

	/**
	 * @param Wpup_Request $request
	 */
	private function generateUniqueQueryArg( $request ): string {
		$parts = array(
			'action'  => 'download',
			'slug'    => (string) $request->slug,
			'version' => (string) $request->wpVersion,
			'url'     => (string) $request->wpSiteUrl,
		);

		return urlencode( base64_encode( hash( WP_UPDATE_HASH_ALGO, implode( ';', $parts ) ) ) );
	}


	protected function checkAuthorization( $request ) {
		parent::checkAuthorization( $request );

		//Prevent download if the user doesn't have a valid license.
		$authHash = $request->param( 'uid' ) ;
		if ( 'download' === $request->action && ! $this->isHashValid( $request ) ) {
			if ( empty( $authHash ) ) {
				$message = 'You must provide a license key to download this plugin.';
			} else {
				$message = 'Sorry, your license is not valid.';
			}
			$this->exitWithError( $message, 403 );
		}
	}
}
