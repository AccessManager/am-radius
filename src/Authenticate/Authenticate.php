<?php

namespace AccessManager\Radius\Authenticate;
use Illuminate\Database\Capsule\Manager as Capsule;
use AccessManager\Radius\Traits\RadiusConstructor;
// use AccessManager\Radius\Helpers\UserProfile;
use AccessManager\Radius\Lib\PolicySchema;

class Authenticate {

	use RadiusConstructor;

	public function checkAccountStatus()
	{
		if( ! $this->plan->isActive() )
			reject("Account Not Active.");
		return $this;
	}

	public function checkRechargeStatus()
	{
		$expiration = $this->plan->getExpiry();
		if(  $expiration == NULL || strtotime($expiration) < time() )
			reject("Account Not Recharged.");
		return $this;
	}

	public function isAllowed()
	{
		$policy = $this->plan->getPolicy();

		if( $policy instanceof PolicySchema ) {
			$tpl = $policy->{date('l')}();
			
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
		if( $this->plan->isLimited() && $this->plan->limitExpired() && ! $this->plan->aq_access )
			reject("Time/Data Limit Exceeded.");
		return $this;
	}

}

//end of file Authenticate.php