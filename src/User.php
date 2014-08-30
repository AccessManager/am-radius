<?php

namespace AccessManager\Radius;
use AccessManager\Radius\Helpers\Database as DB;
use Illuminate\Database\Capsule\Manager as Capsule;
use AccessManager\Radius\Authenticate\PolicySchema;
use Exception as Exception;

class User {

	private $uname = NULL;
	private $user = NULL;
	private $status = [];

	public static function find($uname)
	{
		return new self($uname);
	}

	public function fetchAccount($acctsessionid = NULL, $acctuniqueid = NULL)
	{
		$q = Capsule::table('user_accounts as u')
						->select('u.uname','u.status','u.clear_pword'
							,'r.time_limit','r.data_limit','r.expiration','r.aq_invocked'
							,'v.plan_type','v.policy_type','v.policy_id','v.sim_sessions','v.interim_updates'
							,'l.limit_type','l.aq_access','l.aq_policy'
							)
						->leftJoin('user_recharges as r','r.user_id','=','u.id')
						->leftJoin('prepaid_vouchers as v','v.id','=','r.voucher_id')
						->leftJoin('voucher_limits as l','l.id','=','v.limit_id')
						->where('u.uname', $this->uname);
		
		if( $acctsessionid == NULL && $acctuniqueid == NULL ) {
			$settings = Capsule::table('general_settings as s')
								->select('s.idle_timeout')
								->first();
			if($settings->idle_timeout != 0 )
				$this->user->idleTimeout = $settings->idle_timeout;
		}
		if( $acctsessionid != NULL AND $acctuniqueid != NULL) {
			$q->join('radacct as a','u.uname','=','a.username')
				->addSelect('a.acctinputoctets','a.acctoutputoctets',
							'a.acctsessiontime','r.active_tpl');
		}
		$this->user = $q->first();
		
		if( $this->user == NULL )
			reject("No such user: $this->uname");

		$this->status['isActive'] = $this->user->status;
		$this->status['isLimited'] = $this->user->plan_type;
		$this->status['isUnlimited'] = ! $this->user->plan_type;
		$this->status['haveAQAccess'] = $this->user->aq_access;
		return $this;
	}

	public function getPolicy()
	{
		switch ($this->user->policy_type) {
			case 'Policy':
				return Capsule::table('voucher_bw_policies as p')
						->where('p.id',$this->user->policy_id)
						->first();
				break;
			
			case 'PolicySchema':
				return new PolicySchema($this->user->policy_id);
				break;
		}
	}

	public function limitExpired()
	{
		if( $this->user->plan_type == LIMITED AND $this->user->aq_invocked == TRUE ) {
			return TRUE;
		}
		if( $this->user->limit_type == TIME_LIMIT OR $this->user->limit_type == BOTH_LIMITS ) {
			return $this->user->time_limit <= 0;
		}
		if( $this->user->limit_type == DATA_LIMIT OR $this->user->limit_type == BOTH_LIMITS ) {
			return $this->user->data_limit <= 0;
		}
	}

	public function haveTimeLimit()
	{
		if( $this->user->limit_type == TIME_LIMIT OR $this->user->limit_type == BOTH_LIMITS ) {
			return TRUE;
		}
		return FALSE;
	}

	public function haveDataLimit()
	{
		if( $this->user->limit_type == DATA_LIMIT OR $this->user->limit_type == BOTH_LIMITS ) {
			return TRUE;
		}
		return FALSE;
	}

	public function __call($name, $arguments)
	{
		if( array_key_exists($name, $this->status) ) {
			return $this->status[$name];
		}
		throw new Exception("Method not found.");
	}

	public function __get($name)
	{
		if( property_exists($this->user, $name) ) {
			return $this->user->$name;
		}
	}

	public function __set($name, $value)
	{
		if( property_exists($this->user, $name) ) {
			throw new Exception("Cannot overwrite property. Not Allowed.");
		}
	}

	public function __toString()
	{
		return $this->uname;
	}

	public function __construct($uname)
	{
		DB::connect();
		$this->uname = $uname;
	}
}

//end of file User.php