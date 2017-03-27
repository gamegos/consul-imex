<?php
namespace Gamegos\ConsulImex\Command;

/* Imports from symfony/console */
use Symfony\Component\Console\Command\HelpCommand as BaseHelpCommand;

/**
 * Help Command
 * @author Safak Ozpinar <safak@gamegos.com>
 */
class HelpCommand extends BaseHelpCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        if (getenv('RUNNING_IN_CONTAINER')) {
            $fullName = 'docker run consul-imex ' . $this->getName();
        } else {
            $fullName = 'php ' . $_SERVER['PHP_SELF'] . ' ' . $this->getName();
        }

        $this->setHelp(<<<EOF
The <info>%command.name%</info> command displays help for a given command:
  <info>{$fullName} <command_name></info>

You can also output the help in other formats by using the <comment>--format</comment> option:
  <info>{$fullName} --format=xml <command_name></info>

EOF
        );
    }
}
