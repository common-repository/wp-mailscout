<?php

namespace MS\Models;

class Recipient {
	public $name;
	public $email;

	public function __construct( $name, $email ) {
		$this->name  = $name;
		$this->email = $email;
	}
}