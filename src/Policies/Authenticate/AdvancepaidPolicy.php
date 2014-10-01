<?php

namespace AccessManager\Radius\Policies\Authenticate;
use AccessManager\Radius\Interfaces\AuthenticationPolicyInterface;
use AccessManager\Radius\Helpers\Database;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class AdvancepaidPolicy implements AuthenticationPolicyInterface {

	private $policy;

	private function _fetchPolicy($policy_id)
	{
		$this->policy = 	DB::table('ap_policies as bw')
								->select('bw_policy')
								->where('bw.id','=',$policy_id)
								->first();
	}

	public function getBWPolicy()
	{
		return $this->policy->bw_policy;
	}

	public function __get($name)
	{
		if(property_exists($this->policy, $name))
			return $this->policy->$name;
		throw new Exception("No such property: $name.");
	}



	public function __construct($policy_id)
	{
		Database::connect();
		$this->_fetchPolicy($policy_id);
	}
}

//end of file FreePolicy.php