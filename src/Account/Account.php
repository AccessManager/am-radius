<?php
namespace AccessManager\Radius\Account;
use Illuminate\Database\Capsule\Manager as DB;
use AccessManager\Radius\Authenticate\PolicySchema;
use AccessManager\Radius\Authorize\PolicyAttributes;
use AccessManager\Radius\Authorize\PolicySchemaAttributes;
use AccessManager\Radius\Interfaces\ServicePlanInterface;
use Symfony\Component\Process\Process;

class Account {

	private $plan;
	private $policy;
	private $sessionTime;
	private $inputOctets;
	private $outputOctets;
	private $sessionData;
	private $countableTime;
	private $countableData;
	private $shell = NULL;
	private $activeSessions = NULL;

	public function takeTime($time)
	{
		$this->sessionTime = $time;
		return $this;
	}

	public function takeData($inputOctets = 0, $inputGigawords = 0, $outputOctets = 0, $outputGigawords = 0)
	{
		$this->inputOctets = $inputGigawords == 0 ? $inputOctets : $inputOctets + ($inputGigawords * FOUR_GB);
		$this->outputOctets = $outputGigawords == 0 ? $outputOctets : $outputOctets + ($outputGigawords * FOUR_GB);
		$this->sessionData = $this->inputOctets + $this->outputOctets;
		return $this;
	}

	public function setupAccounting()
	{
		$this->policy = $this->plan->getAccountingPolicy($this->sessionTime, $this->inputOctets + $this->outputOctets);
		
		if ( $this->policy->requestCoA() )
			$this->CoA();
		if( $this->policy->requestDisconnect())
			$this->Disconnect();
		return $this;
	}

	public function countTime()
	{
		$this->countableTime = $this->policy->getCountableTime();
		return $this;
	}

	public function countData()
	{
		if ($countableData = $this->policy->getCountableData())
		$this->countableData = $countableData;
		return $this;
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
			$this->activeSessions = DB::table('radacct as a')
									->select('a.nasipaddress','n.secret','a.servicetype',
											'a.framedipaddress','a.acctsessionid')
									->join('nas AS n','n.nasname','=','a.nasipaddress')
									->where('a.username', $this->plan->user->uname)
									->where('a.acctstoptime', NULL)
									->get();
		}
	}

	public function updateDatabase()
	{
		$this->plan->updateQuotaBalance($this->countableTime, $this->countableData);
	}

	private function _invokeCoA($session)
	{
		$this->_makeShell();
		$exec = "echo \" User-Name={$this->plan->user->uname}, Framed-IP-Address= {$session->framedipaddress}, Acct-Session-Id= {$session->acctsessionid}" .

                        $this->shell . " \" | radclient {$session->nasipaddress}:3799 coa {$session->secret}";

        $process = new Process($exec);
        $process->start();
        
		$this->plan->setAQInvocked();
	}

	private function _makeShell()
	{
		$policy = $this->plan->getAuthorizationPolicy();
		
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
		$exec = "echo \" User-Name={$this->plan->uname}, Framed-IP-Address= {$session->framedipaddress},".
                                     " Acct-Session-Id= {$session->acctsessionid} \" | radclient {$session->nasipaddress}:3799 disconnect {$session->secret} ";
		(new Process($exec) )->start();
	}

	public function __construct(ServicePlanInterface $plan)
	{
		$this->plan = $plan;
	}

}

//end of file Account.php