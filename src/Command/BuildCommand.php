<?php
namespace Gamegos\ConsulImex\Command;

/* Imports from symfony/console */
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/* Imports from PHP core */
use Phar;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Build executable phar file for Consul Imex application.
 * @author Safak Ozpinar <safak@gamegos.com>
 */
class BuildCommand extends Command
{
    /**
     * Phar file name.
     * @var string
     */
    const PHAR_FILE = 'consul-imex.phar';

    /**
     * Included paths.
     * @var array
     */
    protected static $include = [
        'src',
        'vendor',
        'scripts',
    ];

    /**
     * Excluded paths.
     * @var array
     */
    protected static $exclude = [
        'vendor/symfony/console/Tests',
        'vendor/symfony/debug/Tests',
    ];

    /**
     * Construct.
     */
    public function __construct()
    {
        parent::__construct('build');
        $this->setDescription('Creates executable phar file for Consul Imex application.');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument('version', InputArgument::OPTIONAL, 'Target version. (Override the VERSION file)');
        $this->addOption('owner-uid', 'u', InputOption::VALUE_OPTIONAL, 'Set the owner user Id of the phar file.');
        $this->addOption('owner-gid', 'g', InputOption::VALUE_OPTIONAL, 'Set the owner GID of the phar file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Remove the existing phar file if exists.
        if (file_exists(self::PHAR_FILE)) {
            Phar::unlinkArchive(self::PHAR_FILE);
        }

        // Create the phar archive.
        $phar = new Phar(
            self::PHAR_FILE,
            FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO,
            self::PHAR_FILE
        );
        $phar->startBuffering();

        // Add the VERSION file.
        $version = $input->getArgument('version');
        if (isset($version)) {
            $phar->addFromString('VERSION', $version);
        } else {
            $phar->addFile('VERSION');
        }

        // Add files.
        foreach (self::$include as $dirname) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirname));
            while ($iterator->valid()) {
                if ($iterator->isFile()) {
                    $path = $iterator->getPathName();
                    if (!$this->isFileExcluded($path)) {
                        if ('php' == strtolower($iterator->getExtension())) {
                            $contents = php_strip_whitespace($path);
                            $phar->addFromString($path, $contents);
                        } elseif ('LICENSE' == $iterator->getFileName()) {
                            $phar->addFile($path);
                        }
                    }
                }
                $iterator->next();
            }
        }

        // Create the default stub.
        $stub = "#!/usr/bin/env php\n"
              . $phar->createDefaultStub('scripts/consul-imex.php');
        $phar->setStub($stub);
        // Compress files.
        $phar->compressFiles(Phar::GZ);
        // Finalize.
        $phar->stopBuffering();

        // Set file mode and owners.
        chmod(self::PHAR_FILE, 0775);
        $user = $input->getOption('owner-uid');
        if (isset($user)) {
            chown(self::PHAR_FILE, (int) $user);
        }
        $group = $input->getOption('owner-gid');
        if (isset($group)) {
            chgrp(self::PHAR_FILE, (int) $group);
        }
    }

    /**
     * Check if a file is excluded.
     * @param  string $filename
     * @return boolean
     */
    protected function isFileExcluded($filename)
    {
        foreach (self::$exclude as $path) {
            if (strpos($filename, $path) === 0) {
                return true;
            }
        }
        return false;
    }
}
