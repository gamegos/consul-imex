<?php
// Import the autoloader.
call_user_func(function () {
    $files = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../../autoload.php',
    ];
    foreach ($files as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
    fwrite(
        STDERR,
        'Cannot find the autoload file!' . PHP_EOL .
        'You must set up the project dependencies using `composer install`' . PHP_EOL
    );
    exit(1);
});

// Read the VERSION file.
$version = file_get_contents(__DIR__ . '/../VERSION');
// Run the application.
(new Gamegos\ConsulImex\Application($version))->run();
