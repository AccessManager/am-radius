<?php

namespace AccessManager\Radius\Policies\Authorize;
use AccessManager\Radius\Interfaces\AuthorizationPolicyInterface;
use AccessManager\Radius\Helpers\AttributesHelper;
use AccessManager\Radius\Helpers\Database;
use AccessManager\Radius\Interfaces\ServicePlanInterface;

// use AccessManager\Radius\Helpers\UserProfile;

class AuthorizationPolicy implements AuthorizationPolicyInterface {

	use AttributesHelper;
	// use UserProfile;
	private $plan;

	public function makeTimeLimit($sessionTime = 0)
	{
		if( $this->plan->isUnlimited() || $this->plan->limitExpired() )
			return $this->_unlimitedTime();
		return $this->_addTimeLimit($sessionTime);
	}

	public function makeDataLimit($sessionData = 0)
	{
		if( $this->plan->isUnlimited() || $this->plan->limitExpired() )
			return $this->_unlimitedData();
		return $this->_addDataLimit($sessionData);
	}
	
	public function makeBWPolicy()
	{
		if( $this->plan->isLimited() && $this->plan->limitExpired() )
			return $this->_addReply(['Mikrotik-Rate-Limit' => $this->plan->aq_policy]);
		return $this->_addReply(['Mikrotik-Rate-Limit' => $this->plan->getPolicy()->getBWPolicy()]);
	}

	public function __construct( ServicePlanInterface $plan)
	{
		$this->plan = $plan;
		Database::connect();
	}
}

//end of file AuthorizationPolicy.php