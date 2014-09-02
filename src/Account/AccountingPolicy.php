<?php
namespace AccessManager\Radius\Account;
use AccessManager\Radius\Interfaces\AccountingInterface;
use AccessManager\Radius\User;

class AccountingPolicy implements AccountingInterface {

	private $user;
	private $sessionTime;
	private $sessionData;

	public function getCountableTime()
	{
		if( $this->user->isUnlimited() || $this->user->aq_invocked )  return FALSE;
		if( $this->user->haveTimeLimit() )
			return $this->sessionTime - $this->user->acctsessiontime;
		return FALSE;
	}

	public function getCountableData()
	{
		if( $this->user->isUnlimited() || $this->user->aq_invocked )	return FALSE;
		if( $this->user->haveDataLimit() )
			return $this->sessionData - ($this->user->acctinputoctets + $this->user->acctoutputoctets);
		return FALSE;
	}

	public function requestCoA()
	{
		if( $this->user->isUnlimited() || $this->user->aq_invocked )	
			return FALSE;
		if( $this->user->isLimited() && ! $this->user->haveAQAccess() )
			return FALSE;

		if( $this->user->haveTimeLimit() && $this->user->time_limit <= 0 ) {
			return TRUE;
		}

		if( $this->user->haveDataLimit() && $this->user->data_limit <= 0 ) {
			return TRUE;
		}
		return FALSE;
	}

	public function requestDisconnect()
	{
		return FALSE;
	}

	public function __construct(User $user, $sessionTime, $sessionData)
	{
		$this->user = $user;
		$this->sessionTime = $sessionTime;
		$this->sessionData = $sessionData;
	}
}

//end of file AccountingPolicy.php