<?php
namespace AccessManager\Radius\Account;
use AccessManager\Radius\Interfaces\AccountingInterface;
use AccessManager\Radius\User;

class AccountingPolicySchema implements AccountingInterface {

	private $user;
	private $tpl;
	private $sessionTime;
	private $sessionData;
	private $coa = FALSE;

	public function getCountableTime()
	{
		if( $this->user->isUnlimited() || $this->user->limitExpired() )	return FALSE;

		if( $this->tpl->isAccountable() && $this->user->haveTimeLimit() )
			return $this->sessionTime - $this->user->acctsessiontime;
		return FALSE;
	}

	public function getCountableData()
	{
		if( $this->user->isUnlimited() || $this->user->limitExpired() )	return FALSE;

		if( $this->tpl->isAccountable() && $this->user->haveDataLimit() )
			return $this->sessionData - ($this->user->acctinputoctets + $this->user->acctoutputoctets);
		return FALSE;
	}

	public function requestCoA()
	{
		$this->_checkCoAStatus();
		return $this->coa;
	}

	private function _checkCoAStatus()
	{
		if( $this->user->isUnlimited() || $this->user->aq_invocked || ! $this->tpl->isAccountable() )
			return $this->coa = FALSE;

		if( $this->user->haveTimeLimit() )
			return $this->coa = ( $this->user->time_limit - $this->getCountableTime() ) <= 0;

		if( $this->user->haveDataLimit() )
			return $this->coa = ( $this->user->data_limit - $this->getCountableData() ) <= 0;

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
		return $this->user->active_tpl != $this->tpl->id;
	}

	public function __construct(User $user, SchemaTemplate $template, $sessionTime, $sessionData)
	{
		$this->user = $user;
		$this->tpl = $template;
		$this->sessionTime = $sessionTime;
		$this->sessionData = $sessionData;
	}

}

//end of file AccountingPolicySchema.php