<?php
// Import the autoloader.
require __DIR__ . '/autoload.php';

// Set the current working directory.
chdir(__DIR__ . '/..');

// Run the application.
$app = new Symfony\Component\Console\Application();
$app->add(new Gamegos\ConsulImex\Command\BuildCommand());
$app->setDefaultCommand('build', true);
$app->run();
