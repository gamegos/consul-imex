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
        "\033[31mCannot find the autoload file!\033[0m" . PHP_EOL .
        "\033[33mYou must set up the project dependencies using 'composer install'\033[0m" . PHP_EOL
    );
    exit(1);
});
