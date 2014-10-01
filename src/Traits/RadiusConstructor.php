<?php

namespace AccessManager\Radius\Traits;
use AccessManager\Radius\Interfaces\ServicePlanInterface;
use AccessManager\Radius\Helpers\Database;

trait RadiusConstructor {
	private $plan;

	public function __construct(ServicePlanInterface $plan)
	{
		$this->plan = $plan;
		Database::connect();
	}
}

//end of file RadiusConstructor.php