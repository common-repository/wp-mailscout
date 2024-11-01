<?php

namespace MS\Listeners;

use MS\Repositories\BroadcastRepository;
use MS\Services\Crypto;
use MS\Services\GMailService;

class URLRewriteListener {

	const REWRITE_URL = 'mailscout';
	const PIXEL_PATH = 'sig';
	const REDIRECT_PATH = 'redirect';
	const OAUTH_PATH = 'oauth';

	/**
	 * @param $encrypted_info
	 *
	 * @return string
	 */
	public static function getPixelURL( $encrypted_info ) {
		return get_site_url() . '/' . static::REWRITE_URL . '/' . static::PIXEL_PATH . '/' . urlencode( $encrypted_info );
	}

	/**
	 * @param $encrypted_info
	 *
	 * @return string
	 */
	public static function getRedirectURL( $encrypted_info ) {
		return get_site_url() . '/' . URLRewriteListener::REWRITE_URL . '/' . static::REDIRECT_PATH . '/' . urlencode( $encrypted_info );
	}

    /**
     * get the redirect url for oauth
     *
     * @return string
     */
    public static function getOAuthRedirectURL()
    {
        return GMailService::getRedirectUri() . '/redirect';
	}

	/**
	 * Pixel request from mails to track opens
	 *
	 * @param $encrypted
	 */
	public static function pixelRequest( $encrypted ) {
		$decoded = Crypto::decrypt( $encrypted );
		$attrs   = json_decode( $decoded );

		if ( ! $attrs ) {
			return;
		}

		// we have an open
		BroadcastRepository::Instance()->updateByHash( $attrs->hash, [ 'opened' => 1 ] );

		header( 'Content-Type: image/png' );
		echo base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=' );
		exit;
	}

	/**
	 * Attach invisible pixel to message body
	 *
	 * @param $hash
	 * @param $message
	 *
	 * @return mixed
	 */
	public static function attachPixel( $hash, $message ) {
		$encoded = Crypto::encrypt( json_encode( [ 'hash' => $hash ] ) );
		$url     = static::getPixelURL( $encoded );
		$img     = "<img src='{$url}' width='0' height='0'/>";

		return $message . $img;
	}

	/**
	 * Redirect request from mails to track clicks
	 *
	 * @param $encoded
	 */
	public static function redirectRequest( $encoded ) {
		$decoded = Crypto::decrypt( $encoded );
		$attrs   = json_decode( $decoded );

		if ( ! $attrs ) {
			// invalid
			return;
		}

		// update click
		BroadcastRepository::Instance()->updateByHash( $attrs->hash, [ 'clicked' => 1 ] );

		// redirect now
		if ( wp_redirect( $attrs->url ) ) {
			exit;
		}
	}
}
