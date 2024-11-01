<?php

namespace MS\Concerns;

use MS\Services\Request;

trait HasRequest {
	/** @var Request $request */
	public $request;

	public function __construct() {
		$this->request = Request::Instance();
	}
}
