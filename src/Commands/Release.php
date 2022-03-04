<?php
declare(strict_types=1);

namespace Nextcloud\DevCli\Commands;

use Nextcloud\DevCli\Context\AppContext;
use Nextcloud\DevCli\Context\GitContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Release extends Command {

	private AppContext $appContext;
    private GitContext $gitContext;
    private Changelog $changelog;

    private InputInterface $input;
    private OutputInterface $output;

    private bool $shouldExecute = false;

	public function __construct(AppContext $appContext, GitContext $gitContext, Changelog $changelog) {
		parent::__construct();

		$this->appContext = $appContext;
        $this->gitContext = $gitContext;
        $this->changelog = $changelog;
	}

	protected function configure(): void {
		$this->setName('release')
			->addOption('run')
			->addOption('skip-dirty-check')
			;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->appContext->getAppInfo();
		if (!$this->appContext->isInAppContext()) {
			$output->writeln('<error>No app context found.</error>');
            return Command::FAILURE;
        }

		if ($input->getOption('run')) {
			$this->shouldExecute = true;
		}

		$this->input = $input;
		$this->output = $output;

		exec("git status --porcelain", $statusOutput, $result);
		$statusOutput = implode("\n", $statusOutput);
		if ($statusOutput !== '' && !$input->getOption('skip-dirty-check') && $this->shouldExecute) {
			$output->writeln('<error>Git repository should be clean.</error>');
			return Command::INVALID;
		}

		// Release metadata
		$release = [
			'version' => $this->appContext->getAppInfo()->version, // TODO already get new version or set later
			'org-release' => $this->gitContext->getGithubOrg() === 'nextcloud' ? 'nextcloud-releases' : $this->gitContext->getGithubOrg(),
			'org' => $this->gitContext->getGithubOrg(),
			'repo' => $this->gitContext->getGithubRepo(),
			'app' => $this->appContext->getAppInfo()->id,
			'branch' => $this->gitContext->getBranchName(),
		];

		$nextcloudVersions = $this->appContext->getAppInfo()->dependencies->nextcloud;
		$minVersion = $nextcloudVersions['min-version'] ?? null;
		$maxVersion = $nextcloudVersions['max-version'] ?? null;
		$versionRange = ($minVersion ?? '') . '-' . ($maxVersion ?? '');
		if ((string)$minVersion === (string)$maxVersion) {
			$versionRange = $maxVersion;
		}

		$output->writeln("Preparing to release version ${release['version']} from ${release['org']}/${release['org']} at ${release['branch']}");
		$output->writeln('Compatible Nextcloud versions: ' . $versionRange);
		$this->output->writeln('');


		$this->output->writeln('Prepare release commit');
		// - bump version
		// - generate changelog
		$this->executeShell("ndc changelog");
		$this->executeShell("vim CHANGELOG.md");

		# create commit for version bump
		$this->executeShell("git add CHANGELOG.md appinfo/info.xml package.json composer.json"); // TODO ask for confirmation of commit
		$this->executeShell("git status", false, true, true);
		$this->executeShell("git diff --cached", false, true, true);
		$this->confirmOrQuit('Commit the changes');
		$this->executeShell("git commit -m 'Bump version to ${release['version']}' --signoff");
		$this->executeShell("git push");
		$this->output->writeln('');


		$this->output->writeln('Build release artefact');
		$this->executeShell("krankerl package");
		$this->output->writeln('');


		// TODO: wait for ci, currently cannot find any way to fetch github action results per commit


		# Tag release and push
		$this->output->writeln('Tag release and push');
		$this->executeShell("git tag v${release['version']}");
		$this->executeShell("git push -u origin v${release['version']}");
		$this->executeShell("git push -u release v${release['version']}");
		$this->output->writeln('');


		// Publish release
		$this->output->writeln('Publish release');
		$this->executeShell("changelog-parser | jq '.versions[0] | .body' -r > /tmp/releasenotes");
		$this->executeShell("cat /tmp/releasenotes");
		$this->executeShell("gh release --repo ${release['org-release']}/${release['repo']} create v${release['version']} ./build/artifacts/${release['app']}.tar.gz -F /tmp/releasenotes -t v${release['version']}");
		$this->executeShell("krankerl publish https://github.com/${release['org-release']}/${release['repo']}/releases/download/v${release['version']}/${release['app']}.tar.gz");
		$this->output->writeln('');


		$this->output->writeln('🚀 Published to the app store: https://apps.nextcloud.com/apps/' . $this->appContext->getAppInfo()->id);
		$this->output->writeln('');

		// system("vim file.name > `tty`");
		
		return Command::SUCCESS;
	}

	private function executeShell(string $cmd, $checkReturnValue = true, $outputAlways = false, $alwaysExecute = false): bool {
		$this->output->writeln('  <fg=bright-blue>$ ' . $cmd . '</>');
		if (!$alwaysExecute && !$this->shouldExecute) {
			return true;
		}
		if (!$alwaysExecute) { return true; } // FIXME debug saveguard
		$return = exec($cmd, $output, $resultCode);
		if ($this->output->isVerbose() || $outputAlways) {
			foreach ($output as $line) {
				$this->output->writeln('    <fg=#aaaaaa>' . $line . '</>');
			}
		}
		return $return && (!$checkReturnValue || $resultCode === 0);
	}

	private function confirmOrQuit(string $question): void {
		if (!$this->shouldExecute) {
			return;
		}

		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion($question . ' (yes|<options=bold>no</>)', false);

		if (!$helper->ask($this->input, $this->output, $question)) {
			die();
		}
	}
}
