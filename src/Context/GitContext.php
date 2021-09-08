<?php
declare(strict_types=1);

namespace Nextcloud\DevCli\Context;

use Github\Client;

class GitContext {

	private ConfigContext $configContext;

	public function __construct(ConfigContext $configContext) {
		$this->configContext = $configContext;
	}

	public function isGitClean(): bool {
		return trim(preg_replace('/\s\s+/', ' ', shell_exec('git status --porcelain'))) === '';
	}

	public function getBranchName(): string {
		return trim(preg_replace('/\s\s+/', ' ', shell_exec('git branch --show-current')));
	}

	public function getGithubRepo(): string {
		$remote = trim(preg_replace('/\s\s+/', ' ', shell_exec('git remote get-url origin')));
		preg_match('/(.*):(.*)\/(.*)\.git/', $remote, $matches);
		return $matches[3];
	}

	public function getGithubOrg(): string {
		$remote = trim(preg_replace('/\s\s+/', ' ', shell_exec('git remote get-url origin')));
		preg_match('/(.*):(.*)\/(.*)\.git/', $remote, $matches);
		return $matches[2];
	}

	public function getClient(): Client {
		$githubToken = $this->configContext->getGithubToken();
		if ($githubToken === null) {
			throw new \InvalidArgumentException('No github token provided');
		}
		$client = new Client();
		$client->authenticate($githubToken, Client::AUTH_ACCESS_TOKEN);
		return $client;
	}

}
