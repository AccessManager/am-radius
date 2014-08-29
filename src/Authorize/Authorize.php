<?php

namespace AccessManager\Radius\Authorize;
use Illuminate\Database\Capsule\Manager as Capsule;
use AccessManager\Radius\Authorize\PolicyAttributes;
use AccessManager\Radius\Authorize\PolicySchemaAttributes;
use AccessManager\Radius\Authenticate\PolicySchema;
use AccessManager\Radius\Helpers\AttributesHelper;
use AccessManager\Radius\User;

class Authorize {

	use AttributesHelper;

	private $user;
	private $policy;

	public function makeCheck()
	{
		$this->_addCheck([
			'Cleartext-Password'	=>	$this->user->clear_pword,
					'Expiration'	=>	$this->user->expiration,
			  'Simultaneous-Use'	=>	$this->user->sim_sessions,
			]);
		return $this;
	}

	public function makeReply()
	{
		$this->policy->makeTimeLimit();
		$this->policy->makeDataLimit();
		$this->policy->makeBWPolicy();
		$this->_replyCommon();
		$this->reply = $this->reply + $this->policy->getReplyAttributes();
		return $this;
	}

	public function updateRadius()
	{
		Capsule::transaction(function(){
			Capsule::table('radcheck')
					->where('username', $this->user)
					->delete();
			Capsule::table('radreply')
					->where('username', $this->user)
					->delete();
			Capsule::table('radcheck')
					->insert($this->radcheck);
			Capsule::table('radreply')
					->insert($this->radReply);
		});
	}

	private function _replyCommon()
	{
		$this->_addReply([
			'Acct-Interim-Interval'		=>	$this->user->interim_updates,
			]);
		if( ! is_null($this->user->idleTimeout) )
			$this->_addReply(['Idle-Timeout'	=>	$this->user->idleTimeout]);
	}

	public function __construct(User $user)
	{
		$this->user = $user;
		$policy = $user->getPolicy();

		if( $policy instanceof PolicySchema ) {
			$this->policy = new PolicySchemaAttributes($user, $policy->{date('l')}() );
		} else {
			$this->policy = new PolicyAttributes($user);
		}
	}

}

//end of file Authorize.php