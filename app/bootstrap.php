<?php

require __DIR__ . '/../vendor/autoload.php';
\Kdyby\Replicator\Container::register();

$configurator = new Nette\Configurator;

$configurator->setDebugMode(TRUE);  // debug mode MUST NOT be enabled on production server
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->addDirectory(__DIR__ . '/../vendor/kdyby')
	->addDirectory(__DIR__ . '/../vendor/ajax')
	->addDirectory(__DIR__ . '/../vendor/others')
	->addDirectory(__DIR__ . '/../vendor/nextras')
	->addDirectory(__DIR__ . '/../vendor/twbs')
	->addDirectory(__DIR__ . '/../vendor/mesour')
	->addDirectory(__DIR__ . '/../vendor/radekdostal')
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');
$configurator->addConfig(__DIR__ . '/config/config.product.neon');

$container = $configurator->createContainer();

return $container;



