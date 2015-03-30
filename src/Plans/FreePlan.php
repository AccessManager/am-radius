<?php

namespace AccessManager\Radius\Plans;
use AccessManager\Radius\Traits\ServicePlanTrait;
use AccessManager\Radius\Interfaces\ServicePlanInterface;
use AccessManager\Radius\Policies\Authenticate\FreePolicy;
use AccessManager\Radius\Policies\Authorize\AuthorizationPolicy;
use AccessManager\Radius\Policies\Account\AccountingPolicy;
use Illuminate\Database\Capsule\Manager as DB;

class FreePlan implements ServicePlanInterface {

	use ServicePlanTrait;
	private $query;

	public function fetchPlanDetails( $acctsessionid = FALSE, $acctuniqueid = FALSE )
	{
		if($acctsessionid && $acctuniqueid) {
			$this->_fetchAuthorizationWithAccounting($acctsessionid, $acctuniqueid);
		} else {
			$this->_fetchAuthorizationOnly();
		}
	}

	private function _fetchAuthorizationOnly()
	{
		$this->_buildAuthorizationQuery();
				
		$this->plan = $this->query->first();
		if( $this->plan == NULL )
			reject("Account Not Recharged: {$this->user->uname}");
	}

	private function _buildAuthorizationQuery()
	{
		$this->query = DB::table('user_accounts as u')
							->leftJoin('free_balance as b','b.user_id','=','u.id')
							->where('u.id',$this->user->id)
							->select('b.time_balance as time_limit','b.data_balance as data_limit', 
								'b.expiration', 'u.clear_pword','b.plan_type','b.limit_type',
								'b.bw_policy','b.sim_sessions','b.aq_access','b.aq_policy',
								'b.interim_updates','b.aq_invocked'
								);
	}

	private function _fetchAuthorizationWithAccounting($acctsessionid, $acctuniqueid)
	{
		$this->_buildAuthorizationQuery();
		$this->query
					->join('radacct AS a','u.uname','=','a.username')
					->where('a.acctsessionid', $acctsessionid)
					->where('a.acctuniqueid', $acctuniqueid)
					->addSelect('a.acctinputoctets','a.acctoutputoctets','a.acctsessiontime');
		$this->plan = $this->query->first();
	}

	public function limitExpired()
	{
		if( $this->plan->plan_type == LIMITED && $this->plan->aq_invocked == TRUE ) {
			return TRUE;
		}
		if( $this->plan->limit_type == TIME_LIMIT || $this->plan->limit_type == BOTH_LIMITS ) {
			return $this->plan->time_limit <= 0;
		}
		if( $this->plan->limit_type == DATA_LIMIT || $this->plan->limit_type == BOTH_LIMITS ) {
			return $this->plan->data_limit <= 0;
		}
	}

	public function haveTimeLimit()
	{
		if( $this->plan->limit_type == TIME_LIMIT || $this->plan->limit_type == BOTH_LIMITS )
			return TRUE;
		return FALSE;
	}

	public function haveDataLimit()
	{
		if( $this->plan->limit_type == DATA_LIMIT || $this->plan->limit_type == BOTH_LIMITS )
			return TRUE;
		return FALSE;
	}

	public function isAllowed()
	{
		return TRUE;
	}

	public function isActive()
	{
		return $this->user->status == ACTIVE ? TRUE : FALSE;
	}

	public function isLimited()
	{
		return $this->plan->plan_type;
	}

	public function isUnlimited()
	{
		return ! $this->plan->plan_type;
	}

	public function haveAQAccess()
	{
		return $this->plan->aq_access;
	}

	public function getExpiry()
	{
		return $this->plan->expiration;
	}

	public function getPolicy()
	{
		return new FreePolicy($this->plan->bw_policy);
	}

	public function getAuthorizationPolicy()
	{
		return new AuthorizationPolicy($this);
	}

	public function getAccountingPolicy($sessionTime, $sessionData)
	{
		return new AccountingPolicy($this, $sessionTime, $sessionData);
	}

	public function updateQuotaBalance($countableTime, $countableData)
	{
		$q = DB::table('free_balance')
				->where('user_id', $this->user->id);
		if( $countableTime != NULL && $countableTime != FALSE )
		$q->decrement('time_balance', $countableTime);
		if( $countableData != NULL && $countableData != FALSE )
		$q->decrement('data_balance', $countableData);
	}

	public function setAQInvocked()
	{
		DB::table('free_balance')
					->where('user_id',$this->user->id)
					->update(['aq_invocked'=>1]);
	}

}

//end of file FreePlan.php