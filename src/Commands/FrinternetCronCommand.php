<?php

namespace AccessManager\Radius\Commands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AccessManager\Radius\Crons\FrinternetCron;

class FrinternetCronCommand extends Command {

	protected function configure()
	{
		$this->setName('cron:frinternet:reset');

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		( new FrinternetCron() )
			->resetQuotaBalance();
	}
}

//end of file FrinternetCronCommand.php