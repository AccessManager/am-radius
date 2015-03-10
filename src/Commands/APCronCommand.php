<?php

namespace AccessManager\Radius\Commands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AccessManager\Radius\Crons\APCron;

class APCronCommand extends Command {

	protected function configure()
	{
		$this->setName('cron:ap:reset');
	}

	protected function execute( InputInterface $input, OutputInterface $output )
	{
		( new APCron )
			->resetQuotaBalance();
	}
}
// end of file APCronCommand.php