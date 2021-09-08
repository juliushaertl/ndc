<?php
declare(strict_types=1);

namespace Nextcloud\DevCli\Commands\Create;

use Nextcloud\DevCli\Context\AppContext;
use Symfony\Component\Console\Command\Command;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class BaseCreator extends Command {

	private $inAppContext = false;

	public function __construct(AppContext $appContext) {
		parent::__construct(null);

		$this->appContext = $appContext;

		$loader = new FilesystemLoader(__DIR__ . '/stubs');
		$this->twig = new Environment($loader, []);
	}

	public function validateAppContext() {
		// check if in app
		// otherwise print
	}

	public function createClassFromStub(string $stub, string $target, $context = []): void{
		$appContext = [
			'namespace' => $this->getClassBase($target),
			'class' => $this->getClassName($target)
		];
		if (!$this->inAppContext) {
			$template = $this->twig->load($stub . '.php.twig');
			echo $template->render(array_merge($appContext, $context));
		}
	}

	protected function getLibPath(): string {
		return $this->appContext->getAppPath() . '/lib';
	}


	protected function buildNewClassNamespace(string $inputName, string $libSubdirectory = null, $normalizeClassPostfix = null): string {
		$inputName = $this->normalizeClass($inputName);
		$baseNamespace = $this->appContext->getAppNamespace() . ($libSubdirectory ? "\\" . $libSubdirectory : '');
		if (strpos($inputName, $baseNamespace) === 0) {
			$inputName = substr($inputName, strlen($this->appContext->getAppNamespace()));
		}
		if (strpos($inputName, $libSubdirectory) === 0) {
			$inputName = substr($inputName, strlen($libSubdirectory));
		}
		$inputName = trim($inputName, '\\');
		return $this->normalizeClass($baseNamespace . "\\" . $inputName, $normalizeClassPostfix);
	}

	protected function normalizeClass(string $class, string $normalizeClassPostfix = null): string {
		$class = str_replace('/', '\\', $class);
		$parts = explode('\\', $class);
		$parts = array_map(fn($part) => ucfirst($part), $parts);
		$classNameIndex = count($parts)-1;
		if ($normalizeClassPostfix) {
			// endure that class always ends with postfix if provided
			$parts[$classNameIndex] = str_replace($normalizeClassPostfix . $normalizeClassPostfix, $normalizeClassPostfix, ($parts[$classNameIndex] . $normalizeClassPostfix));
		}
		return implode('\\', $parts);
	}

	protected function getClassName(string $classFqn): string {
		$parts = explode('\\', $classFqn);
		return end($parts);
	}

	protected function getClassBase(string $classFqn): string {
		$parts = explode('\\', $classFqn);
		array_pop($parts);
		return implode('\\', $parts);
	}
}
