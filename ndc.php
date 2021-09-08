#!/usr/bin/env php
<?php

use Nextcloud\DevCli\Application;

require __DIR__.'/vendor/autoload.php';

// FIXME: Remove - Testing only
$appDir = getenv('APPDIR');
if (!empty($appDir)) {
	chdir($appDir);
}

$application = new Application();
$application->run();
