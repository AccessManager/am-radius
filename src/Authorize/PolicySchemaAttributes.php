<?php

namespace AccessManager\Radius\Authorize;
use AccessManager\Radius\User;
use AccessManager\Radius\Interfaces\AttributesInterface;

class PolicySchemaAttributes Implements  AttributesInterface {

	// use UserProfile;
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

	public function makeBWPolicy()
	{
		if( $this->tpl->haveFullDayAccess() ) {
			if( $this->tpl->isAccountable() && $this->user->limitExpired() ) {
				return $this->_addReply(['Rate-Limit'=>$this->user->aq_policy]);
			} else {
			return $this->_addReply(['Rate-Limit'=>$this->tpl->bw_policy]);
			}
		}

		if( $this->tpl->isInPrimaryTime() ) {
			if( $this->tpl->isPrimaryAccountable() && $this->user->limitExpired() ) {
				return $this->_addReply(['Rate-Limit'=>$this->user->aq_policy]);
			} else {
				return $this->_addReply( ['Rate-Limit'=>$this->tpl->pr_policy] );
			}
		}

		if( $this->tpl->isInSecondaryTime() ) {
			if( $this->tpl->isSecondaryAccountable() && $this->user->limitExpired() ) {
				return $this->_addReply(['Rate-Limit'=>$this->user->aq_policy]);
			} else {
				return $this->_addReply( ['Rate-Limit'=>$this->tpl->sec_policy] );
			}
		}
	}

	public function __construct(User $user, SchemaTemplate $template)
	{
		$this->user = $user;
		$this->tpl = $template;
	}

}

//end of file SchemaAttributeMaker.php