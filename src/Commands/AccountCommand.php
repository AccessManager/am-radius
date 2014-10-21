<?php
namespace AccessManager\Radius\Commands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AccessManager\Radius\Account\Account;
use AccessManager\Radius\UserAccount;

class AccountCommand extends command {

	protected function configure()
	{
		$this->setName("am:account")
			->setDescription("Takes care of accounting user's data.")
			->addArgument("params",InputArgument::REQUIRED, "Require params for Accounting.");
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		 $z = $input->getArgument('params');
		$attributes = parseAttributes($z);
		$params = ['User-Name','Acct-Session-Id','Acct-Unique-Session-Id',
					'Acct-Input-Octets','Acct-Output-Octets','Acct-Input-Gigawords',
					'Acct-Output-Gigawords','Acct-Session-Time','Acct-Status-Type'];
		foreach($params as $param) {
			if( ! array_key_exists($param, $attributes)) {
				$output->writeln("Insufficient/Invalid parameters provided.");
				exit();
			}
		}
		$requestType = $attributes['Acct-Status-Type'];
		if( $requestType == 'Start')	exit(0);
	
	 		 $username = $attributes['User-Name'];
		$acctsessionid = $attributes['Acct-Session-Id'];
		 $acctuniqueid = $attributes['Acct-Unique-Session-Id'];
		  $sessiontime = $attributes['Acct-Session-Time'];
		  $inputoctets = $attributes['Acct-Input-Octets'];
		 $outputoctets = $attributes['Acct-Output-Octets'];
			$inputgigs = $attributes['Acct-Input-Gigawords'];
		   $outputgigs = $attributes['Acct-Output-Gigawords'];

		   $user = new UserAccount($username);
			$plan = $user->getActivePlan();
			$plan->fetchPlanDetails($acctsessionid, $acctuniqueid);

		$account = new Account($plan);

		$account->takeTime($sessiontime)
				->takeData($inputoctets, $inputgigs, $outputoctets, $outputgigs)
				->setupAccounting()
				->countTime()
				->countData()
				->updateDatabase();
		if( $requestType != 'Stop' )
		{
			$account->disconnectExpired();
		}
	}
	
}

//end of file AccountCommand.php