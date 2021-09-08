<?php
declare(strict_types=1);

namespace Nextcloud\DevCli\Commands;

use Github\Api\Repo;
use Github\Exception\RuntimeException;
use Nextcloud\DevCli\Context\AppContext;
use Nextcloud\DevCli\Context\GitContext;
use Nextcloud\DevCli\Generator\Changelog as GeneratorChangelog;
use Spatie\Async\Pool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Changelog extends Command {

	private $changelogEntries = [];

	public function __construct(private AppContext $appContext, private GitContext $gitContext, private GeneratorChangelog $changelogGenerator) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('changelog')
			->addOption('exclude-dependabot', 'd')
			->addOption('branch', 'b', InputOption::VALUE_REQUIRED)
			;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->appContext->getAppInfo();
		if (!$this->appContext->isInAppContext()) {
			$output->writeln('<error>No app context found.</error>');
			return 1;
		}

		$branch = $input->getOption('branch') ?? $this->gitContext->getBranchName();

		$currentVersion = $this->appContext->getAppInfo()->version;

		$previousVersion = $this->getPreviousVersion($currentVersion);
		if ($previousVersion === null) {
			$output->writeln('<error>Could not find matching previous version for ' . $currentVersion. '.</error>');
			return 1;
		}

		$output->writeln('<info>Fetching changelog on branch ' . $branch. ' until ' . $previousVersion . ' for version ' . $currentVersion. '</info>');

		$stringOutput = new BufferedOutput();

		$pullRequests = $this->changelogGenerator->fetchPullrequests($input->getOption('branch') ?? null, $previousVersion, $output);
		$this->changelogGenerator->processPullRequests($pullRequests, $output);
		$this->changelogGenerator->getChangelogEntry($currentVersion, $stringOutput);

		$output->writeln($stringOutput->fetch());

		return 0;
	}

	private function getPreviousVersion(string $version): ?string {
		$currentVersion = new \Nextcloud\DevCli\Model\Version($version);

		$tags = array_map(
			fn($tag) => $tag['name'],
			$this->gitContext->getClient()
				->api('repo')
				->tags($this->gitContext->getGithubOrg(), $this->gitContext->getGithubRepo())
		);

		$previousPatch = $currentVersion->getPatch() !== 0 ? (
			$currentVersion->getMajor() . '.' . $currentVersion->getMinor() . '.' . ($currentVersion->getPatch()-1)
		) : null;
		$previousMinor = $currentVersion->getMinor() !== 0 ? (
			$currentVersion->getMajor() . '.' . ($currentVersion->getMinor()-1)
		) : null;
		$previousMajor = $currentVersion->getMajor() !== 0 ? (
			(string)($currentVersion->getMajor() - 1)
		) : null;

		$matchingPatch = $previousPatch ? $this->findLatestMatchingVersion($tags, $previousPatch) : null;
		if ($matchingPatch) {
			return $matchingPatch;
		}
		$matchingMinor = $previousMinor ? $this->findLatestMatchingVersion($tags, $previousMinor) : null;
		if ($matchingMinor) {
			return $matchingMinor;
		}
		$matchingMajor = $previousMajor ? $this->findLatestMatchingVersion($tags, $previousMajor) : null;
		if ($matchingMajor) {
			return $matchingMajor;
		}

		return null;
	}

	private function findLatestMatchingVersion(array $tags, string $match): ?string {
		$tags = array_filter($tags, function ($tag) use ($match) {
			return strpos($tag, 'v' . $match) === 0;
		});
		asort($tags);
		$matched = end($tags);
		return $matched ?: null;
	}
}
