<?php

namespace AccessManager\Radius\Policies\Account;
use AccessManager\Radius\Interfaces\AccountingPolicyInterface;
use AccessManager\Radius\Interfaces\ServicePlanInterface;

class AccountingPolicy implements AccountingPolicyInterface {
	
	private $plan;
	private $sessionTime;
	private $sessionData;

	public function getCountableTime()
	{
		if( $this->plan->isUnlimited() ||( $this->plan->isLimited() && $this->plan->aq_invocked ) )	return FALSE;

		if( $this->plan->haveTimeLimit() )
			return $this->sessionTime - $this->plan->acctsessiontime;
		return FALSE;
	}

	public function getCountableData()
	{
		if( $this->plan->isUnlimited() || ( $this->plan->isLimited() && $this->plan->aq_invocked ) )	return FALSE;

		if( $this->plan->haveDataLimit() )
			return $this->sessionData - ( $this->plan->acctinputoctets + $this->plan->acctoutputoctets );
		return FALSE;
	}

	public function requestCoA()
	{
		if( $this->plan->isUnlimited() || $this->plan->aq_invocked )
			return FALSE;
		if( $this->plan->isLimited() && ! $this->plan->haveAQAccess() )
			return FALSE;
		if( $this->plan->haveDataLimit() && $this->plan->data_limit <= 0 )
			return TRUE;
		if( $this->plan->haveTimeLimit() && $this->plan->time_limit <= 0 )
			return TRUE;
		return FALSE;
	}

	public function requestDisconnect()
	{
		return FALSE;
	}

	public function __construct( ServicePlanInterface $plan, $sessionTime, $sessionData)
	{
		$this->plan = $plan;
		$this->sessionTime = $sessionTime;
		$this->sessionData = $sessionData;
	}
}

//end of file AccountingPolicy.php