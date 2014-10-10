<?php

namespace AccessManager\Radius\Authorize;
use Illuminate\Database\Capsule\Manager as DB;
use AccessManager\Radius\Policies\Authorize\PolicyAttributes;
use AccessManager\Radius\Policies\Authorize\PolicySchemaAttributes;
use AccessManager\Radius\Policies\Authenticate\Prepaid\PolicySchema;
use AccessManager\Radius\Helpers\AttributesHelper;
use AccessManager\Radius\Interfaces\ServicePlanInterface;
use AccessManager\Radius\Helpers\Database;

class Authorize {

	use AttributesHelper;

	private $plan;

	public function makeCheck()
	{
		$this->_addCheck([
			'Cleartext-Password'	=>	$this->plan->user->clear_pword,
					'Expiration'	=>	$this->plan->getExpiry(),
			  'Simultaneous-Use'	=>	$this->plan->sim_sessions,
			]);
		return $this;
	}

	public function makeReply()
	{
		$policy = $this->plan->getAuthorizationPolicy();
		$policy->makeTimeLimit();
		$policy->makeDataLimit();
		$policy->makeBWPolicy();

		$this->_replyCommon();
		$this->reply = array_merge($this->reply, $policy->getReplyAttributes());
		return $this;
	}

	public function updateRadius()
	{
		DB::transaction(function(){
			DB::table('radcheck')
					->where('username', $this->plan->user->uname)
					->delete();
			DB::table('radreply')
					->where('username', $this->plan->user->uname)
					->delete();
			DB::table('radcheck')
					->insert($this->check);
			DB::table('radreply')
					->insert($this->reply);
		});
	}

	private function _replySubnet()
	{
		$framedip = DB::table('subnet_ips as ip')
						->where('user_id', $this->plan->user->id)
						->select('ip.ip')
						->first();

		if( ! is_null($framedip) ) {
			$static_ip = long2ip($framedip->ip);
			$this->_addReply([
				'Framed-IP-Address'	=>	$static_ip,
				]);
		}

		$route = DB::table('user_routes as r')
						->where('r.user_id',$this->plan->user->id)
						->select('r.subnet')
						->first();
		if( ! is_null($route) ) {
			if( is_null($framedip) ) {
				$this->_addReply([
						'Framed-Route'		=>		"{$route->subnet} 0.0.0.0 1",
					]);
			} else {
				$static_ip = long2ip($framedip->ip);
				$this->_addReply([
						'Framed-Route'		=>		"{$route->subnet} {$static_ip} 1",
					]);
			}
		}
	}

	private function _replyCommon()
	{
		$this->_addReply([
			'Acct-Interim-Interval'		=>	$this->plan->interim_updates,
			]);
		if( ! is_null($this->plan->idleTimeout) )
			$this->_addReply(['Idle-Timeout'	=>	$this->plan->idleTimeout]);
		$this->_replySubnet();
	}

	public function __construct(ServicePlanInterface $plan)
	{
		$this->plan = $plan;
		Database::connect();
	}

}

//end of file Authorize.php