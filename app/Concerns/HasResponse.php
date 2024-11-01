<?php

namespace MS\Concerns;

trait HasResponse {

	/**
	 * @param $data
	 *
	 * @param int $code
	 *
	 * @return false|mixed|string
	 */
	public function response( $data, $code = null ) {
		if ( $code === null ) {
			return wp_send_json_success( $data );
		}

		return wp_send_json_error( $data, $code );
	}
}
