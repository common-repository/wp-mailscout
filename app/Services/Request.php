<?php

namespace MS\Services;

class Request {
	/** @var static $instance */
	private static $instance;
	/** @var array $data */
	private $data;
	/** @var array $files */
	private $files;
	/** @var string $method */
	public $method;

	/**
	 * Request constructor.
	 */
	private function __construct() {
		$post      = $_POST;
		$get       = $_GET;
		$json      = [];
		$jsonInput = file_get_contents( 'php://input' );;
		if ( strlen( $jsonInput ) > 0 ) {
			$json = json_decode( $jsonInput, 1 );
		}
		$this->files  = $_FILES;
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->data   = array_merge( $get, $post, $json );
	}

	/**
	 * get the request instance
	 *
	 * @return Request
	 */
	public static function Instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * check if a file exists
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function hasFile( $key ) {
		return isset( $this->files[ $key ] );
	}

	/**
	 * check if a key exists
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function has( $key ) {
		return $this->hasInput( $key ) || $this->hasFile( $key );
	}

	/**
	 * checks if input exists
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function hasInput( $key ) {
		return isset( $this->data[ $key ] );
	}

	/**
	 * get file
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function file( $key ) {
		if ( ! $this->hasFile( $key ) ) {
			return null;
		}

		return $this->files[ $key ];
	}

	/**
	 * get specific input
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function get( $key ) {
		if ( ! $this->has( $key ) ) {
			return $this->file( $key );
		}

		return $this->data[ $key ];
	}

	/**
	 * get specific input
	 * does not return file
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function input( $key ) {
		if ( ! $this->has( $key ) ) {
			return null;
		}

		return $this->data[ $key ];
	}

	/**
	 * get all params
	 *
	 * @return object
	 */
	public function all() {
		return $this->data;
	}

	/**
	 * get all files
	 *
	 * @return array
	 */
	public function files() {
		return $this->files;
	}
}
