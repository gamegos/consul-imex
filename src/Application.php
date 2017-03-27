<?php
namespace Gamegos\ConsulImex;

/* Imports from symfony/console */
use Symfony\Component\Console\Application as BaseApplication;

/**
 * Consul Imex Application
 * @author Safak Ozpinar <safak@gamegos.com>
 */
class Application extends BaseApplication
{
    /**
     * Construct.
     */
    public function __construct()
    {
        parent::__construct('Consul Imex');

        $this->addCommands([
            new Command\ImportCommand(),
            new Command\ExportCommand(),
            new Command\CopyCommand(),
        ]);
        $this->setDefaultCommand('help');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultCommands()
    {
        return [new Command\HelpCommand()];
    }
}
