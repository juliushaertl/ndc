<?php
declare(strict_types=1);

namespace Nextcloud\DevCli\Context;

class ConfigContext {

	private $config = [];

	public function __construct() {
		$configPath =  $_SERVER['HOME'] . '/.nextcloud/ndc';
		if (file_exists($configPath)) {
			$this->config = include $configPath;
		}
	}

	public function getGithubToken(): ?string {
		// TODO use env if present

		if (isset($this->config['github_token'])) {
			return $this->config['github_token'];
		}

		return null;
	}
}
