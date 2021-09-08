<?php
declare(strict_types=1);

namespace Nextcloud\DevCli\Model;

use SimpleXMLElement;

class AppInfo {

	public $id;
	public $name;
	public $summary;
	public $description;

	public $version;
	public $namespace;

	public function __construct(SimpleXMLElement $xmlSource) {
		if (!isset($xmlSource->id)) {
			throw new \InvalidArgumentException('Invalid appinfo passed');
		}
		$this->xmlSource = $xmlSource;
		$this->id = $xmlSource->id;
		$this->name = $xmlSource->name;
		$this->summary = $xmlSource->summary;
		$this->description = $xmlSource->description;

		$this->dependencies = $xmlSource->dependencies;
		$this->namespace = (string)$xmlSource->namespace;

		$this->version = (string)$xmlSource->version;
	}

	public function getXMLElement(): SimpleXMLElement {
		return $this->xmlSource;
	}

	public function setVersion(string $version): self {
		$this->version = $version;
		$this->xmlSource->version = $version;
		return $this;
	}
}
