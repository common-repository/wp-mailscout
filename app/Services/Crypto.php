<?php

namespace MS\Services;

class Crypto {
	const KEY = 'woieuf#&*&@EIGDO8ni';

	/**
	 * Encrypt
	 *
	 * @param $text
	 *
	 * @return string
	 */
	public static function encrypt( $text ) {
		return openssl_encrypt( $text, "AES-128-ECB", static::KEY );
	}

	/**
	 * Decrypt
	 *
	 * @param $encoded
	 *
	 * @return string
	 */
	public static function decrypt( $encoded ) {
		return openssl_decrypt( $encoded, "AES-128-ECB", static::KEY );
	}
}
