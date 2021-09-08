<?php
declare(strict_types=1);

namespace Nextcloud\DevCli\Commands\Create;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Controller extends BaseCreator {
	protected function configure(): void {
		$this
			->setName('create:controller')
			->setDescription('Creates a new controller class')
			->addArgument('className', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$inputName = $input->getArgument('className');
		$className = $this->buildNewClassNamespace($inputName, 'Controller', 'Controller');
		$this->createClassFromStub('Controller', $className, []);
		$output->writeln('Created controller ' . $className);
		return 0;
	}
}
