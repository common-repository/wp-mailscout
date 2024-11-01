<?php

namespace MS\Abstracts;

use MS\Concerns\HasRequest;
use MS\Concerns\HasResponse;

abstract class Controller {
	use HasRequest, HasResponse;

}
