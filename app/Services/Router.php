<?php

namespace MS\Services;

class Router {
	private static $routes = [
		'get'    => [],
		'post'   => [],
		'put'    => [],
		'patch'  => [],
		'delete' => [],
	];
	/** @var Request $request */
	private $request;

	/**
	 * Router constructor.
	 */
	public function __construct() {
		require_once mailscout_get_file_path( 'admin/ajax-routes.php' );
		$this->request = Request::Instance();
	}

	/**
	 * @return mixed|null
	 * @throws \Exception
	 */
	public function callRoute() {
		if ( ! $this->request->has( 'route' ) ) {
			return null;
		}
		$route          = $this->request->get( 'route' );
		$request_method = strtolower( $this->request->method );
		$controller     = null;

		foreach ( self::$routes[ $request_method ] as $r => $c ) {
			if ( $route == $r ) {
				$controller = $c;
				break;
			}
		}

		if ( $controller == null ) {
			throw new \Exception( "Invalid Request" );
		}

		$parts             = explode( '@', $controller );
		$controller        = 'MS\Controllers\\' . $parts[0];
		$controller_method = $parts[1];

		if ( ! class_exists( $controller ) ) {
			throw new \Exception( "Class {$controller} does not exist" );
		}
		$instance = new $controller;
		if ( ! method_exists( $instance, $controller_method ) ) {
			throw new \Exception( "Method {$controller_method} does not exist in class {$controller}" );
		}

		return call_user_func_array( array( $instance, $controller_method ), [] );
	}

	private static function add( $method, $route, $callable ) {
		static::$routes[ $method ][ $route ] = $callable;
	}

	public static function get( $route, $callable ) {
		self::add( 'get', $route, $callable );
	}

	public static function post( $route, $callable ) {
		self::add( 'post', $route, $callable );
	}

	public static function put( $route, $callable ) {
		self::add( 'put', $route, $callable );
	}

	public static function patch( $route, $callable ) {
		self::add( 'patch', $route, $callable );
	}

	public static function delete( $route, $callable ) {
		self::add( 'delete', $route, $callable );
	}
}
