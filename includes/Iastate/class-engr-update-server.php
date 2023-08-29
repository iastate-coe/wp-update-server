<?php

class Engr_UpdateServer extends Wpup_UpdateServer {
	/**
	 * @var string|null
	 */
	private $authenticationKey;

	/**
	 * @param null $serverUrl
	 * @param null $serverDirectory
	 */
	public function __construct( $serverUrl = null, $serverDirectory = null ) {
		parent::__construct( $serverUrl, $serverDirectory );
		$this->logDirectory = WP_UPDATE_ROOT_PATH . '/logs';
		$this->cache        = new Wpup_FileCache( WP_UPDATE_ROOT_PATH . '/cache' );
	}

	public static function isSsl(): bool {
		//Sanitization is not needed here because the value is only checked against known values.
		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
			return true;
		}

		return parent::isSsl();
	}

	public function requireAuthentication( string $authenticationKey ): self {
		$this->authenticationKey = base64_encode( $authenticationKey );

		return $this;
	}

	public function setCache( Wpup_Cache $cacheController ): self {
		$this->cache = $cacheController;

		return $this;
	}

	/**
	 * @param array $query
	 * @param array $headers
	 *
	 * @return Wpup_Request
	 */
	protected function initRequest( $query = null, $headers = null ): Wpup_Request {
		/**
		 * @var Engr_Request|Wpup_Request $request
		 */
		$request = parent::initRequest( $query, $headers );

		//phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$request->clientIp = filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_VALIDATE_IP );
		}

		//Load the license, if any.
		if ( ! empty( $request->headers->get( 'Authorization' ) ) ) {
			$request->authKey = urldecode( str_replace( 'Basic ', '', $request->headers->get( 'Authorization' ) ) );
		}

		return $request;
	}

	/**
	 * @param array $meta
	 * @param Engr_Request|Wpup_Request $request
	 *
	 * @return array
	 */
	protected function filterMetadata( $meta, $request ): array {
		$meta = parent::filterMetadata( $meta, $request );

		if ( isset( $request->authKey ) ) {
			$meta['auth_key'] = $request->authKey;
		}

		//Only include the download URL if the license is valid.
		if ( $this->isAuthenticationKeyValid( $request->authKey ) ) {
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
	private function isAuthenticationKeyValid( string $string ): bool {
		return $this->authenticationKey === $string;
	}

	/**
	 * @param Engr_Request|Wpup_Request $request
	 */
	private function generateUniqueQueryArg( $request ): string {
		$parts      = array(
			'action'  => 'download',
			'slug'    => (string) $request->slug,
			'version' => (string) $request->wpVersion,
			'url'     => (string) $request->wpSiteUrl,
		);
		$query_hash = hash( WP_UPDATE_HASH_ALGO, implode( ';', $parts ) );

		return rawurlencode( base64_encode( $query_hash ) );
	}

	/**
	 * @param Engr_Request|Wpup_Request $request
	 *
	 * @return void
	 * @uses exit() if criteria isn't met.
	 */
	protected function checkAuthorization( $request ) {
		parent::checkAuthorization( $request );

		//Prevent download if the user doesn't have a valid license.
		$authHash = $request->param( 'uid' );
		if ( 'download' === $request->action && ! $this->isHashValid( $request ) ) {
			if ( empty( $authHash ) ) {
				$message = 'You must provide a license key to download this plugin.';
			} else {
				$message = 'Sorry, your license is not valid.';
			}
			$this->exitWithError( $message, 403 );
		}
	}

	/**
	 * @param Engr_Request|Wpup_Request $request
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
}
