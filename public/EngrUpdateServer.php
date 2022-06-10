<?php

class Engr_UpdateServer extends Wpup_UpdateServer {
	private $authenticationKey;

	public function __construct( $serverUrl = null, $serverDirectory = null, $authenticationKey = null ) {
		parent::__construct( $serverUrl, $serverDirectory );
		$this->authenticationKey = $authenticationKey;
	}

	protected function initRequest( $query = null, $headers = null ) {
		$request = parent::initRequest( $query, $headers );

		//Load the license, if any.
		$authKey = null;
		if ( $request->param( 'auth_key' ) ) {
			$authKey = $request->param( 'auth_key' );
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
			$args                 = array( 'auth_key' => $request->param( 'auth_key' ) );
			$meta['download_url'] = self::addQueryArg( $args, $meta['download_url'] );
		} else {
			//No license = no download link.
			unset( $meta['download_url'] );
		}

		return $meta;
	}

	private function isAuthenticationKeyValid( $string ): bool {
		return $this->authenticationKey === $string;
	}

	protected function checkAuthorization( $request ) {
		parent::checkAuthorization( $request );

		//Prevent download if the user doesn't have a valid license.
		$authKey = $request->authKey;
		if ( $request->action === 'download' && ! $this->isAuthenticationKeyValid( $authKey ) ) {
			if ( ! isset( $authKey ) ) {
				$message = 'You must provide a license key to download this plugin.';
			} else {
				$message = 'Sorry, your license is not valid.';
			}
			$this->exitWithError( $message, 403 );
		}
	}
}
