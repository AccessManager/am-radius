<?php

namespace AccessManager\Radius\Policies\Account;
use AccessManager\Radius\Interfaces\AccountingPolicyInterface;
use AccessManager\Radius\Interfaces\ServicePlanInterface;

class AccountingPolicySchema implements AccountingPolicyInterface {

	private $plan;
	private $tpl;
	private $sessionTime;
	private $sessionData;
	private $coa = FALSE;

	public function getCountableTime()
	{
		if( $this->plan->isUnlimited() || $this->plan->limitExpired() )	return FALSE;

		if( $this->tpl->isAccountable() && $this->plan->haveTimeLimit() )
			return $this->sessionTime - $this->plan->acctsessiontime;
		return FALSE;
	}

	public function getCountableData()
	{
		if( $this->plan->isUnlimited() || $this->plan->limitExpired() )	return FALSE;

		if( $this->tpl->isAccountable() && $this->plan->haveDataLimit() )
			return $this->sessionData - ($this->plan->acctinputoctets + $this->plan->acctoutputoctets);
		return FALSE;
	}

	public function requestCoA()
	{
		$this->_checkCoAStatus();
		return $this->coa;
	}

	private function _checkCoAStatus()
	{
		if( $this->plan->isUnlimited() || $this->plan->aq_invocked || ! $this->tpl->isAccountable() )
			return $this->coa = FALSE;

		if( $this->plan->haveTimeLimit() )
			return $this->coa = ( $this->plan->time_limit - $this->getCountableTime() ) <= 0;

		if( $this->plan->haveDataLimit() )
			return $this->coa = ( $this->plan->data_limit - $this->getCountableData() ) <= 0;

		if( $this->_timeChanged() && $this->tpl->isAllowed() )
			return $this->coa = TRUE;
	}

	public function requestDisconnect()
	{
		if( $this->_timeChanged() && ! $this->tpl->isAllowed() )
			return TRUE;
		return FALSE;
	}

	private function _timeChanged()
	{
		return $this->plan->active_tpl != $this->tpl->id;
	}
	
	public function __construct(ServicePlanInterface $plan, $sessionTime, $sessionData)
	{
		$this->plan = $plan;
		$this->sessionTime = $sessionTime;
		$this->sessionData = $sessionData;
		$this->tpl = $plan->getPolicy()
						  ->{date('l')}();
	}
}

//end of file AccountingPolicySchema.php