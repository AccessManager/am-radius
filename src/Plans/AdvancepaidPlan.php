<?php

namespace AccessManager\Radius\Plans;
use DomainException;
use AccessManager\Radius\Traits\ServicePlanTrait;
use AccessManager\Radius\Interfaces\ServicePlanInterface;
use AccessManager\Radius\Policies\Authenticate\AdvancepaidPolicy;
use AccessManager\Radius\Policies\Authenticate\AdvancepaidPolicySchema;
use AccessManager\Radius\Policies\Authorize\AuthorizationPolicy;
use AccessManager\Radius\Policies\Authorize\AuthorizationPolicySchema;
use AccessManager\Radius\Policies\Account\AccountingPolicy;
use AccessManager\Radius\Policies\Account\AccountingPolicySchema;
use Illuminate\Database\Capsule\Manager as DB;

class AdvancepaidPlan Implements ServicePlanInterface {

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
		$this->_makeAuthorizationQuery();
		$this->plan = $this->query->first();
		if(is_null($this->plan))
			reject("Service Plan not assigned.");
	}

	private function _fetchAuthorizationWithAccounting($acctsessionid, $acctuniqueid)
	{
		$this->_makeAuthorizationQuery();
		$this->query->join('radacct as a','a.username','=','u.uname')
					->where('a.acctsessionid', $acctsessionid)
					->where('a.acctuniqueid', $acctuniqueid)
					->addSelect('a.acctinputoctets','a.acctoutputoctets',
								'a.acctsessiontime','p.active_tpl');
		$this->plan = $this->query->first();
	}

	private function _makeAuthorizationQuery()
	{
		$this->query = DB::table('user_accounts as u')
						->join('ap_active_plans as p','p.user_id','=','u.id')
						->leftJoin('ap_limits as l','l.id','=','p.limit_id')
						->select('u.clear_pword','p.time_balance as time_limit',
							'p.data_balance as data_limit','p.aq_invocked','p.plan_type',
							'p.policy_type','p.policy_id','p.sim_sessions','p.interim_updates',
							'l.limit_type','l.aq_access','l.aq_policy')
						->where('user_id', $this->user->id);
	}

	public function limitExpired()
	{
		if( $this->plan->aq_invocked == TRUE )
			return TRUE;
		switch($this->plan->limit_type) {
			case TIME_LIMIT:
				return $this->plan->time_limit <= 0;
				break;
			case DATA_LIMIT:
				return $this->plan->data_limit <= 0;
				break;
			case BOTH_LIMITS:
				return $this->plan->time_limit <= 0 || $this->plan->data_limit <= 0;
		}
	}

	public function haveTimeLimit()
	{
		return $this->plan->limit_type == TIME_LIMIT || $this->plan->limit_type == BOTH_LIMITS;
	}

	public function haveDataLimit()
	{
		return $this->plan->limit_type == DATA_LIMIT || $this->plan->limit_type == BOTH_LIMITS;
	}

	public function isAllowed()
	{
		return TRUE;
	}

	public function isActive()
	{
		return $this->user->status;
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
		return $this->plan->aq_access ? TRUE : FALSE;
	}

	public function getExpiry()
	{
		$billing_cycle = DB::table('billing_cycles as c')
						->where('user_id',$this->user->id)
						->select('c.expiration')
						->first();
		$expiration = strtotime($billing_cycle->expiration);

		if( $expiration <= strtotime('1970-01-02') )
			return date("d M Y H:i", strtotime("+1 Month"));
		return date('d M Y H:i', $expiration);
	}

	public function getPolicy()
	{
		switch( $this->plan->policy_type) {
			case 'Policy' :
			return new AdvancepaidPolicy($this->plan->policy_id);
			break;
			case 'PolicySchema' :
			return new AdvancepaidPolicySchema($this->plan->policy_id);
			break;
			default:
			throw new DomainException("Unknown policy type: {$this->plan->policy_type}");
		}
	}

	public function getAuthorizationPolicy()
	{
		switch( $this->plan->policy_type ) {
			case 'Policy':
				return new AuthorizationPolicy($this);
				break;
			case 'PolicySchema':
				return new AuthorizationPolicySchema($this);
				break;
			default:
				throw new DomainException("Unknown Policy Type: {$this->plan->policy_type}");
				break;
		}
	}

	public function getAccountingPolicy($sessionTime, $sessionData)
	{
		switch( $this->plan->policy_type ) {
			case 'Policy' :
			return new AccountingPolicy($this, $sessionTime, $sessionData);
			break;
			case 'PolicySchema' :
			return new AccountingPolicySchema($this, $sessionTime, $sessionData);
			break;
			default:
			throw new DomainException("Unknown Policy Type: {$this->plan->policy_type}");
			break;
		}
	}

	public function updateQuotaBalance($countableTime, $countableData)
	{
		$q = DB::table('ap_active_plans')
				->where('user_id', $this->user->id);
		if( $countableTime != NULL && $countableTime != FALSE )
			$q->decrement('time_balance', $countableTime);
		if( $countableData != NULL && $countableData != FALSE )
			$q->decrement('data_balance', $countableData);
	}

	public function setAQInvocked()
	{
		DB::table('ap_active_plans')
			->where('user_id', $this->user->id)
			->update(['aq_invocked'=>1]);
	}

}

//end of file BillingPlan.php