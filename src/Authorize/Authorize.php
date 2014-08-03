<?php

namespace AccessManager\Radius\Authorize;
use Illuminate\Database\Capsule\Manager as Capsule;
use AccessManager\Radius\Authorize\PolicyAttributes;
use AccessManager\Radius\Authorize\PolicySchemaAttributes;
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
		$this->reply = array_merge($this->reply, $this->policy->getReplyAttributes());
		return $this;
	}

	public function updateRadius()
	{
		echo "<pre>";
			print_r($this->check);
			print_r($this->reply);
		echo "</pre>";
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
		$policy = $this->user->getPolicy();

		if( is_a($policy,'AccessManager\Radius\Authenticate\PolicySchema')) {
			$this->policy = new PolicySchemaAttributes($user, $policy->{date('l')}() );
		} else {
			$this->policy = new PolicyAttributes($user);
		}
	}

}

//end of file Authorize.php