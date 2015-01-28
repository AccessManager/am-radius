<?php

namespace AccessManager\Radius\Policies\Authorize;
use AccessManager\Radius\Interfaces\AuthorizationPolicyInterface;
use AccessManager\Radius\Interfaces\ServicePlanInterface;
use AccessManager\Radius\Helpers\AttributesHelper;

class AuthorizationPolicySchema implements AuthorizationPolicyInterface {

		use AttributesHelper;
	private $user;
	private $tpl;


	public function makeTimeLimit($sessionTime = 0)
	{
		if( $this->user->isUnlimited() || $this->user->limitExpired() || ! $this->tpl->isAccountable() )
			 return $this->_unlimitedTime();

		if( $this->tpl->haveFullDayAccess() && $this->tpl->isAccountable() )
			return $this->_addTimeLimit($sessionTime);

		if( $this->tpl->isInPrimaryTime() && $this->tpl->isPrimaryAccountable() )
			 return $this->_addTimeLimit($sessionTime);

		if( $this->tpl->isInSecondaryTime() && $this->tpl->isSecondaryAccountable() )
			 return $this->_addTimeLimit($sessionTime);
	}

	public function makeDataLimit($sessionData = 0)
	{
		if( $this->user->isUnlimited() || $this->user->limitExpired() || ! $this->tpl->isAccountable() )
			return $this->_unlimitedData();

		if( $this->tpl->haveFullDayAccess() && $this->tpl->isAccountable() )
			return $this->_addDataLimit($sessionData);

		if( $this->tpl->isInPrimaryTime() && $this->tpl->isPrimaryAccountable() )
			return $this->_addDataLimit($sessionData);

		if( $this->tpl->isInSecondaryTime() && $this->tpl->isSecondaryAccountable() )
			return $this->_addDataLimit($sessionData);
	}

	public function makeBWPolicy($primaryPolicy = FALSE)
	{
		if( $this->tpl->haveFullDayAccess() ) {
			if( $this->tpl->isAccountable() && $this->user->limitExpired() && ! $primaryPolicy ) {
				return $this->_addReply(['Mikrotik-Rate-Limit'=>$this->user->aq_policy]);
			} else {
			return $this->_addReply(['Mikrotik-Rate-Limit'=>$this->tpl->bw_policy]);
			}
		}

		if( $this->tpl->isInPrimaryTime() ) {
			if( $this->tpl->isPrimaryAccountable() && $this->user->limitExpired() && ! $primaryPolicy ) {
				return $this->_addReply(['Mikrotik-Rate-Limit'=>$this->user->aq_policy]);
			} else {
				return $this->_addReply( ['Mikrotik-Rate-Limit'=>$this->tpl->pr_policy] );
			}
		}

		if( $this->tpl->isInSecondaryTime() ) {
			if( $this->tpl->isSecondaryAccountable() && $this->user->limitExpired() && ! $primaryPolicy ) {
				return $this->_addReply(['Mikrotik-Rate-Limit'=>$this->user->aq_policy]);
			} else {
				return $this->_addReply( ['Mikrotik-Rate-Limit'=>$this->tpl->sec_policy] );
			}
		}
	}

	public function __construct(ServicePlanInterface $plan)
	{
		$this->user = $plan;
		$this->tpl = $plan->getPolicy()
						  ->{date('l')}();
	}
	
}

//end of file AuthorizationPolicySchema.php