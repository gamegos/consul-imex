<?php
// Import the autoloader.
require __DIR__ . '/autoload.php';

// Read the VERSION file.
$version = file_get_contents(__DIR__ . '/../VERSION');
// Run the application.
(new Gamegos\ConsulImex\Application($version))->run();
