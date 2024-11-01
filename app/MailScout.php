<?php

namespace MS;


use MS\Services\Router;

class MailScout {

	const PLUGIN_NAME = 'mailscout';
	const PLUGIN_VERSION = '1.0.0';
	const TEXT_DOMAIN = 'mailscout';

	/**
	 * @var Router
	 */
	private $router;

	/**
	 * MailScout constructor.
	 * @throws \Exception
	 */
	public function __construct() {
		$this->router = new Router();
	}

	/**
	 * @throws \Exception
	 */
	public function run() {
		print $this->router->callRoute();
	}
}
