<?php

namespace AccessManager\Radius\Traits;
use AccessManager\Radius\UserAccount;
use AccessManager\Radius\Helpers\Database;

trait ServicePlanTrait {

	public $user;
	private $plan;

	public function __construct(UserAccount $user) {
		$this->user = $user;
		Database::connect();
	}

	public function __get($name)
	{
		if( property_exists($this->plan, $name) )
			return $this->plan->$name;
	}

}

//end of file ServicePlanTrait.php