<?php
namespace AccessManager\Radius\Commands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AccessManager\Radius\Account\Account;

class AccountCommand extends command {

	protected function configure()
	{
		$this->setName("am:account")
			->setDescription("Takes care of accounting user's data.")
			->addArgument("username",InputArgument::REQUIRED, "Require username for Accounting.")
			->addOption(
						"acctsessionid",
						NULL,
						InputOption::VALUE_REQUIRED,
						"acctsessionid required to fetch session information",
						0)
			->addOption(
						"acctuniqueid",
						NULL,
						InputOption::VALUE_REQUIRED,
						"acctuniqueid required to fetch session information",
						0
				)
			->addOption(
						"sessiontime",
						NULL,
						InputOption::VALUE_REQUIRED,
						"SessionTime required for counting time."
				)
			->addOption(
						"inputoctets",
						NULL,
						InputOption::VALUE_REQUIRED,
						"InputOctets required for counting data.",
						0
				)
			->addOption(
						"inputoctetgigawords",
						NULL,
						InputOption::VALUE_REQUIRED,
						"InputOctetGigawords required for counting data.",
						0
				)
			->addOption(
						"outputoctets",
						NULL,
						InputOption::VALUE_REQUIRED,
						'OutputOctets required for counting data.',
						0
				)
			->addOption(
						"OutputOctetGigawords",
						NULL,
						InputOption::VALUE_REQUIRED,
						'OutputOctetGigawords required for counting data.',
						0
				);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$username = $input->getArgument('username');
		$acctsessionid = $input->getOption('acctsessionid');
		$acctuniqueid = $input->getOption('acctuniqueid');

		if(! $username OR ! $acctsessionid OR ! $acctuniqueid ) {
			$output->writeln("Insufficient/Invalid parameters provided.");
			exit();
		}
		$account = new Account( User::find($username)->fetchAccount($acctsessionid, $acctuniqueid) );

		  $sessiontime = $input->getOption('sessiontime');
		  $inputoctets = $input->getOption('inputoctets');
		 $outputoctets = $input->getOption('outputoctets');
			$inputgigs = $input->getOption('inputoctetgigawords');
		   $outptugigs = $input->getOption('outputoctetgigawords');

		$account->takeTime($sessiontime);
		$account->takeData($inputoctets, $inputgigs, $outputoctets, $outputgigs);
		$account->setupAccounting();
	}
	
}

//end of file AccountCommand.php