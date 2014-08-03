<?php

namespace AccessManager\Radius\Authenticate;
use Illuminate\Database\Capsule\Manager as Capsule;
use AccessManager\Radius\Helpers\UserProfile;

class Authenticate {

	use UserProfile;

	public function checkAccountStatus()
	{
		if( ! $this->user->isActive() )
			reject("Account Not Active.");
		return $this;
	}

	public function checkRechargeStatus()
	{
		$expiration = $this->user->expiration;
		if(  $expiration == NULL || strtotime($expiration) < time() )
			reject("Account Not Recharged.");
		return $this;
	}

	public function isAllowed()
	{
		$policy = $this->user->getPolicy();

		if( is_a($policy, 'AccessManager\Radius\Lib\PolicySchema') ) {
			$today = date('l');
			$tpl = $policy->$today();
			
			switch ($tpl->access) {
				case ALLOWED:
					break;
				
				case NOT_ALLOWED:
					reject("Network Access not allowed on {$today}s, for this account.");
					break;

				case PARTIAL:
					if( ! $tpl->isAllowed() ) reject("As per your service plan, you're not allowed to login at this moment.");
					break;
			}
		}
		return $this;
	}

	public function checkQuotaStatus()
	{
		if( $this->user->isLimited() && $this->user->limitExpired() && ! $this->user->aq_access ) {
			reject("Time/Data Limit Exceeded.");
		}
		return $this;
	}

}

//end of file Authenticate.php