<?php
declare(strict_types=1);

namespace Nextcloud\DevCli\Commands\Create;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Event extends BaseCreator {
	protected function configure(): void {
		$this
			->setName('create:event')
			->setDescription('Creates a new event class')
			->addArgument('className', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$inputName = $input->getArgument('className');
		$className = $this->buildNewClassNamespace($inputName, 'Event', 'Event');
		$this->createClassFromStub('Event', $className, []);
		$output->writeln('Created Event ' . $className);
		return 0;
	}
}
