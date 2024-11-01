<?php

namespace MS\Services;

use MS\Listeners\URLRewriteListener;

class MessageParser {

	public static function parse( $text, $values, $hash = null ) {
		return preg_replace_callback( "#{{(.*?)}}|href=['|\"](.*?)['|\"]#", function ( $matches ) use ( $values, $hash ) {
			$first_match = strtolower( trim( $matches[1] ) );
			if ( $first_match === '' && isset( $matches[2] ) ) {
				if ( $hash === null ) {
					return $matches[0];
				}
				$url = $matches[2];

				$attrs = json_encode( [
					'hash' => $hash,
					'url'  => $url,
				] );

				$encrypted = Crypto::encrypt( $attrs );

				return 'href="' . URLRewriteListener::getRedirectURL( $encrypted ) . '"';
			} elseif ( isset( $values[ $first_match ] ) ) {
				return $values[ $first_match ];
			}

			return $matches[0];
		}, $text );
	}
}
