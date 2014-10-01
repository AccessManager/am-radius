<?php

namespace AccessManager\Radius\Plans;
use AccessManager\Radius\Traits\ServicePlanTrait;
use AccessManager\Radius\Interfaces\ServicePlanInterface;
use AccessManager\Radius\Policies\Authenticate\PrepaidPolicy;
use AccessManager\Radius\Policies\Authenticate\PrepaidPolicySchema;
use AccessManager\Radius\Policies\Authorize\AuthorizationPolicy;
use AccessManager\Radius\Policies\Authorize\AuthorizationPolicySchema;
use AccessManager\Radius\Policies\Account\AccountingPolicy;
use AccessManager\Radius\Policies\Account\AccountingPolicySchema;
use Illuminate\Database\Capsule\Manager as DB;

class PrepaidPlan implements ServicePlanInterface {

	use ServicePlanTrait;

	public function fetchPlanDetails( $acctsessionid = FALSE, $acctuniqueid = FALSE )
	{
		$q =	DB::table('user_recharges as r')
					->select('r.time_limit','r.data_limit','r.expiration','r.aq_invocked'
						,'v.plan_type','v.policy_type','v.policy_id','v.sim_sessions','v.interim_updates'
						,'l.limit_type','l.aq_access','l.aq_policy'
						)
					->join('prepaid_vouchers as v','v.id','=','r.voucher_id')
					->leftJoin('voucher_limits as l','l.id','=','v.limit_id')
					->where('r.user_id', $this->user->id);
		if( $acctsessionid && $acctuniqueid ) {
			$q->join('user_accounts as u','u.id','=','r.user_id')
				->join('radacct as a','u.uname','=','a.username')
				->where('a.acctsessionid', $acctsessionid)
				->where('a.acctuniqueid', $acctuniqueid)
				->addSelect('a.acctinputoctets','a.acctoutputoctets'
							,'a.acctsessiontime','r.active_tpl');
		}
		$this->plan = $q->first();

		// $log = DB::getQueryLog();
		// $query = end ($log);
		// print_r($query);
		if( $this->plan == NULL )
			reject("Account Not Recharged: {$this->user->uname}");
	}

	public function getPolicy()
	{
		switch( $this->plan->policy_type ) {
			case 'Policy':
				return new PrepaidPolicy($this->plan->policy_id);
				break;
			case 'PolicySchema':
				return new PrepaidPolicySchema($this->plan->policy_id);
				break;
			default:
				throw new DomainException("Unknown Policy Type: {$this->plan->policy_type}");
				break;
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
			case 'Policy':
				return new AccountingPolicy($this, $sessionTime, $sessionData);
				break;
			case 'PolicySchema':
				return new AccountingPolicySchema($this, $sessionTime, $sessionData);
				break;
			default:
			throw new DomainException("Unknown Policy Type: {$this->plan->policy_type}");
			break;
		}
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

	public function updateQuotaBalance($countableTime, $countableData)
	{
		$q = DB::table('user_recharges')
				->where('user_id',$this->user->id);
		if( $countableTime != NULL && $countableTime != FALSE )
		$q->decrement('time_limit', $countableTime);
		if( $countableData != NULL && $countableData != FALSE )
		$q->decrement('data_limit', $countableData);
	}

	public function setAQInvocked()
	{
		DB::table('user_recharges')
					->where('user_id',$this->user->id)
					->update(['aq_invocked'=>1]);
	}

}

//end of file PrepaidPlan.php