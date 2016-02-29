<?php
// Import autoloader.
require __DIR__ . '/../vendor/autoload.php';

$app = new Symfony\Component\Console\Application('Import/Export Tool for Consul Key/Value Storage');
$app->addCommands([
    new Gamegos\ConsulImex\ImportCommand(),
]);
$app->run();
