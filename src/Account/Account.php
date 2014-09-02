<?php
namespace AccessManager\Radius\Account;
use Illuminate\Database\Capsule\Manager as Capsule;
use AccessManager\Radius\Authenticate\PolicySchema;
use AccessManager\Radius\Authorize\PolicyAttributes;
use AccessManager\Radius\Authorize\PolicySchemaAttributes;
use AccessManager\Radius\User;
use Symfony\Component\Process\Process;

class Account {

	private $user;
	private $policy;
	private $sessionTime;
	private $inputOctets;
	private $outputOctets;
	private $sessionData;
	private $countableTime;
	private $countableData;
	private $shell = NULL;
	private $tpl = FALSE;
	private $coa = FALSE;
	private $activeSessions = NULL;

	public function takeTime($time)
	{
		$this->sessionTime = $time;
	}

	public function takeData($inputOctets = 0, $inputGigawords = 0, $outputOctets = 0, $outputGigawords = 0)
	{
		$this->inputOctets = $inputGigawords == 0 ? $inputOctets : $inputOctets + ($inputGigawords * FOUR_GB);
		$this->outputOctets = $outputGigawords == 0 ? $outputOctets : $outputOctets + ($outputGigawords * FOUR_GB);
		$this->sessionData = $this->inputOctets + $this->outputOctets;
	}

	public function setupAccounting()
	{
		$policy = $this->user->getPolicy();

		if( $policy instanceof PolicySchema ) {
			$this->policy = new AccountingPolicySchema($this->user, $this->tpl, $this->sessionTime, $this->inputOctets + $this->outputOctets);
		} else {
			$this->policy = new AccountingPolicy($this->user, $this->sessionTime, $this->inputOctets + $this->outputOctets);
		}
		if ( $this->policy->requestCoA() )
			$this->CoA();
		if( $this->policy->requestDisconnect())
			$this->Disconnect();
	}

	public function countTime()
	{
		$this->countableTime = $this->policy->getCountableTime();
	}

	public function countData()
	{
		if ($countableData = $this->policy->getCountableData())
		$this->countableData = $countableData;
	}

	public function CoA()
	{
		$this->_fetchActiveSessions();
		$this->_makeShell();

		foreach($this->activeSessions as $session ) {
			if( $session->servicetype == 'PPP') {
				$this->_invokeDisconnect($session);
			} else {
				$this->_invokeCoA($session);
			}
		}
	}

	public function Disconnect()
	{
		$this->_fetchActiveSessions();
		foreach($this->activeSessions as $session ) {
			$this->_invokeDisconnect($session);
		}
	}

	private function _fetchActiveSessions()
	{
		if( $this->activeSessions == NULL ) {
			$this->activeSessions = Capsule::table('radacct as a')
									->select('a.nasipaddress','n.secret','a.servicetype',
											'a.framedipaddress','a.acctsessionid')
									->join('nas AS n','n.nasname','=','a.nasipaddress')
									->where('a.username', $this->user)
									->where('a.acctstoptime', NULL)
									->get();
		}
	}

	public function updateDatabase()
	{
		$q = Capsule::table('user_recharges')
				->where('user_id',$this->user->id);
		if( $this->countableTime != NULL || $this->countableTime != FALSE)
		$q->decrement('time_limit', $this->countableTime);
		if($this->countableData != NULL || $this->countableData != FALSE)
		$q->decrement('data_limit', $this->countableData);
	}

	private function _invokeCoA($session)
	{
		$this->_makeShell();
		$exec = "echo \" User-Name={$this->user->uname}, Framed-IP-Address= {$session->framedipaddress}, Acct-Session-Id= {$session->acctsessionid}" .

                        $this->shell . " \" | radclient {$session->nasipaddress}:3799 coa {$session->secret}";

        print_r($exec); exit;

        $process = new Process($exec);
        $process->start();
        while($process->isRunning() )
        	sleep(3);
		Capsule::table('user_recharges')
					->where('user_id',$this->user->id)
					->update(['aq_invocked'=>1]);
	}

	private function _makeShell()
	{
		if($this->tpl) {
			$policy = new PolicySchemaAttributes($this->user, $this->tpl);
		} else {
			$policy = new PolicyAttributes($this->user);
		}
		$policy->makeTimeLimit( $this->sessionTime );
		$policy->makeDataLimit( $this->sessionData );
		$policy->makeBWPolicy();
		$attributes = $policy->getReplyAttributes();
		foreach( $attributes as $attribute ) {
			$this->shell .= ", {$attribute['attribute']} = ";

			if($attribute['attribute'] == 'Mikrotik-Rate-Limit')
				$this->shell .= "'";
			$this->shell .= "{$attribute['value']}";
			if($attribute['attribute'] == 'Mikrotik-Rate-Limit')
				$this->shell .= "'";
		}
	}

	private function _invokeDisconnect($session)
	{
		$exec = "echo \" User-Name={$this->user->uname}, Framed-IP-Address= {$session->framedipaddress},".
                                     " Acct-Session-Id= {$session->acctsessionid} \" | radclient {$session->nasipaddress}:3799 disconnect {$session->secret} ";
		(new Process($exec) )->start();
	}

	public function __construct(User $user)
	{
		$this->user = $user;
		$policy = $this->user->getPolicy();

		if( $policy instanceof PolicySchema ) {
			$this->tpl = $policy->{date('l')}();
			$this->policy = new AccountingPolicySchema($user, $this->tpl, $this->sessionTime, $this->inputOctets + $this->outputOctets );
		} else {
			$this->policy = new AccountingPolicy($user, $this->sessionTime, $this->inputOctets + $this->outputOctets);
		}
	}

}

//end of file Account.php