<?php

namespace AccessManager\Radius\Policies\Authenticate;
use AccessManager\Radius\Interfaces\AuthenticationPolicyInterface;
use AccessManager\Radius\Helpers\Database;
use Illuminate\Database\Capsule\Manager as DB;
use Exception;

class PrepaidPolicy implements AuthenticationPolicyInterface {

	private $policy;

	private function _fetchPolicy($policy_id)
	{
		$this->policy = DB::table('voucher_bw_policies as p')
							->select('bw_policy')
							->where('id',$policy_id)
							->first();
	}

	public function __get($name)
	{
		if( property_exists($this->policy, $name) )
			return $this->policy->$name;
		throw new Exception("Property: $name does not exist.");
	}

	public function getBWPolicy()
	{
		return $this->policy->bw_policy;
	}

	public function __construct($policy_id)
	{
		$this->_fetchPolicy($policy_id);
		Database::connect();
	}
}

//end of file PrepaidPolicy.php