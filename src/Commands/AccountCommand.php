<?php
namespace AccessManager\Radius\Commands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AccessManager\Radius\Account\Account;
use AccessManager\Radius\User;

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

		if( $attributes['Acct-Status-Type'] == 'Start')	exit(0);
	
	 		 $username = $params['User-Name'];
		$acctsessionid = $params['Acct-Session-Id'];
		 $acctuniqueid = $params['Acct-Unique-Session-Id'];
		  $sessiontime = $params['Acct-Session-Time'];
		  $inputoctets = $params['Acct-Input-Octets'];
		 $outputoctets = $params['Acct-Output-Octets'];
			$inputgigs = $params['Acct-Input-Gigawords'];
		   $outputgigs = $params['Acct-Output-Gigawords'];

		$account = new Account( ( new User($username) )->fetchAccount($acctsessionid, $acctuniqueid) );

		$account->takeTime($sessiontime);
		$account->takeData($inputoctets, $inputgigs, $outputoctets, $outputgigs);
		$account->setupAccounting();
		$account->countTime();
		$account->countData();
		$account->updateDatabase();
	}
	
}

//end of file AccountCommand.php