<?php

namespace AccessManager\Radius\Policies\Authenticate;
use AccessManager\Radius\Interfaces\AuthenticationPolicyInterface;
use AccessManager\Radius\Helpers\Database;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class FreePolicy implements AuthenticationPolicyInterface {

	private $policy;

	// private function _fetchPolicy($policy_id)
	// {
	// 	$this->policy = 	DB::table('bw_policies as bw')
	// 							->select('bw.max_down','bw.max_down_unit',
	// 									'bw.min_down','bw.min_down_unit',
	// 									'bw.max_up','bw.max_up_unit',
	// 									'bw.min_up','bw.min_up_unit')
	// 							->where('bw.id','=',$policy_id)
	// 							->first();
	// }

	public function getBWPolicy()
	{
		return $this->policy;
		// return mikrotikRateLimit((array)$this->policy);
	}

	// public function __get($name)
	// {
	// 	if(property_exists($this->policy, $name))
	// 		return $this->policy->$name;
	// 	throw new Exception("No such property: $name.");
	// }



	public function __construct($policy)
	{
		$this->policy = $policy;
		// Database::connect();
		// $this->_fetchPolicy($policy_id);
	}
}

//end of file FreePolicy.php