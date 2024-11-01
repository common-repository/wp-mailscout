<?php

namespace MS\Services;

class Config {
	private static $instance;
	private $config;
	private $file;

	private function __construct() {
		$this->file = plugin_dir_path( __FILE__ ) . '../../data/config.json';
		if ( ! $this->file ) {
			file_put_contents( json_encode( [] ), $this->file );
		}
		$this->load();
	}

	private function load() {
		$this->config = json_decode( file_get_contents( $this->file ), true );
	}

	public function __destruct() {
		file_put_contents( $this->file, json_encode( $this->config ) );
	}

	public static function get( $name ) {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		if ( isset( self::$instance->config[ $name ] ) ) {
			return self::$instance->config[ $name ];
		}

		return null;
	}

	public static function set( $name, $value ) {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		self::$instance->config[ $name ] = $value;
	}
}