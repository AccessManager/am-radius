<?php

namespace AccessManager\Radius\Policies\Authenticate;
use AccessManager\Radius\Interfaces\AuthenticationPolicyInterface;
use AccessManager\Radius\Helpers\Database;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class FreePolicy implements AuthenticationPolicyInterface {

	private $policy;

	public function getBWPolicy()
	{
		return $this->policy;
	}

	public function __construct($policy)
	{
		$this->policy = $policy;
	}
}

//end of file FreePolicy.php