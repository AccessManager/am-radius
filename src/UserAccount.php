<?php
namespace AccessManager\Radius;
use Illuminate\Database\Capsule\Manager as DB;
use AccessManager\Radius\Helpers\Database;
use AccessManager\Radius\Plans\PrepaidPlan;
use AccessManager\Radius\Plans\FreePlan;
use AccessManager\Radius\Plans\AdvancepaidPlan;
use OutOfBoundsException;
use BadMethodCallException;

class UserAccount {

	private $uname;
	private $account;
	private $status = [];

	public function __construct($uname)
	{
		Database::connect();
		$this->uname = $uname;
		$this->_fetchAccountInfo();
	}

	private function _fetchAccountInfo()
	{
		$this->account = DB::table('user_accounts as u')
							->select('u.id','u.uname','u.status','u.clear_pword','u.plan_type')
							->where('uname',$this->uname)
							->first();
		if( $this->account == NULL )
			reject("No Such User: {$this->uname}");

		$this->status['isActive'] = $this->account->status;
	}

	public function getActivePlan()
	{
		switch($this->account->plan_type) {
			case FREE_PLAN:
				return new FreePlan($this);
				break;
			case ADVANCEPAID_PLAN:
				return new AdvancepaidPlan($this);
				break;
			case PREPAID_PLAN:
				return new PrepaidPlan($this);
				break;
		}
	}

	public function __get($name)
	{
		if( property_exists($this->account, $name))
			return $this->account->$name;
		throw new OutOfBoundsException("Property {$name} does not exist.");
	}

	public function __call($name, $args)
	{
		if( array_key_exists($name, $this->status) )
			return $this->status[$name];
		throw new BadMethodCallException ("No Such Method {$name}.");
	}

	public function __toString()
	{
		return $this->account->uname;
	}
}

//end of file UserAccount.php