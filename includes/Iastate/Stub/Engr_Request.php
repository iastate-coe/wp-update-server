<?php

/**
 * Stub class to outline parameters we expect to see in our requests.
 *
 * @private
 * For code completion purposes. Not ever used.
 */
class Engr_Request extends Wpup_Request {
	/**
	 * Include license information in the update metadata. This saves an HTTP request
	 *  or two since the plugin doesn't need to explicitly fetch license details.
	 * @var string
	 */
	public $authKey;
}
