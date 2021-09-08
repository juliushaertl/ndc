<?php
declare(strict_types=1);

namespace Nextcloud\DevCli\Commands\Create;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventListener extends BaseCreator {
	protected function configure(): void {
		$this
			->setName('create:listener')
			->setDescription('Creates a new event listener class')
			->addArgument('className', InputArgument::REQUIRED)
			->addArgument('events', InputArgument::IS_ARRAY);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$inputName = $input->getArgument('className');
		$className = $this->buildNewClassNamespace($inputName, 'Listener', 'EventListener');
		$this->createClassFromStub('EventListener', $className, ['events' => $input->getArgument('events')]);
		$output->writeln('Created EventListener ' . $className);
		return 0;
	}
}
