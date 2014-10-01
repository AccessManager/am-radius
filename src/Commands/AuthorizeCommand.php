<?php
namespace AccessManager\Radius\Commands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AccessManager\Radius\UserAccount;
use AccessManager\Radius\Authenticate\Authenticate;
use AccessManager\Radius\Authorize\Authorize;


class AuthorizeCommand extends command {

	protected function configure()
	{
		$this->setName("am:authorize")
			->setDescription("Authenticate and Authorize user.")
			->addArgument('username',InputArgument::REQUIRED,"Require Username here.");
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$username = $input->getArgument('username');

		$user = new UserAccount($username);
		$plan = $user->getActivePlan();
		$plan->fetchPlanDetails();

		( new Authenticate( $plan ) )
					 ->checkAccountStatus()
					 ->checkRechargeStatus()
					 ->isAllowed()
					 ->checkQuotaStatus();

		( new Authorize($plan) )
					->makeCheck()
				 	->makeReply()
				 	->updateRadius();
	}
}

//end of file AuthorizeCommand.php