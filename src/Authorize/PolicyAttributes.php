<?php

namespace AccessManager\Radius\Authorize;
use AccessManager\Radius\Interfaces\AttributesInterface;
use AccessManager\Radius\Helpers\AttributesHelper;
use AccessManager\Radius\Helpers\UserProfile;

class PolicyAttributes Implements AttributesInterface {

	use AttributesHelper;
	use UserProfile;

	public function makeTimeLimit($sessionTime = 0)
	{
		if( $this->user->isUnlimited() || $this->user->limitExpired() )
			return $this->_unlimitedTime();
		return $this->_addTimeLimit($sessionTime);
	}

	public function makeDataLimit($sessionData = 0)
	{
		if( $this->user->isUnlimited() || $this->user->limitExpired() )
			return $this->_unlimitedData();
		return $this->_addDataLimit($sessionData);
	}
	
	public function makeBWPolicy()
	{
		if( $this->user->isLimited() && $this->user->limitExpired() )
			return $this->_addReply(['Mikrotik-Rate-Limit' => $this->user->aq_policy]);
		return $this->_addReply(['Mikrotik-Rate-Limit' => $this->user->getPolicy()->bw_policy]);
	}

}

//end of file AttributesMaker.php