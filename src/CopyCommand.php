<?php
namespace Gamegos\ConsulImex;

/* Imports from symfony/console */
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Consul Copy
 * @author Safak Ozpinar <safak@gamegos.com>
 */
class CopyCommand extends Command
{
    /**
     * Construct.
     */
    public function __construct()
    {
        parent::__construct('copy');
        $this->setDescription('Copies data between Consul key-value services.');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument('source', InputArgument::REQUIRED, 'Source prefix.');
        $this->addArgument('target', InputArgument::REQUIRED, 'Target prefix.');
        $this->addOption('source-server', 's', InputOption::VALUE_REQUIRED, 'Source server URL.');
        $this->addOption('target-server', 't', InputOption::VALUE_REQUIRED, 'Target server URL. If omitted, source server is used as target server.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Create temporary file name.
        $file = tempnam(sys_get_temp_dir(), 'CIX');

        // Prepare the 'export' command.
        $exportCommand = $this->getApplication()->find('export');
        $exportParams  = [
            'command'  => 'export',
            'file'     => $file,
            '--prefix' => $input->getArgument('source'),
        ];
        if ($input->getOption('source-server') !== null) {
            $exportParams['--url'] = $input->getOption('source-server');
        }

        // Run the 'export' command.
        $exportReturn = $exportCommand->run(new ArrayInput($exportParams), $output);
        if (0 !== $exportReturn) {
            return $exportReturn;
        }

        // Prepare the 'import' command.
        $importCommand = $this->getApplication()->find('import');
        $importParams  = [
            'command'  => 'import',
            'file'     => $file,
            '--prefix' => $input->getArgument('target'),
        ];
        if ($input->getOption('target-server') !== null) {
            $importParams['--url'] = $input->getOption('target-server');
        } elseif ($input->getOption('source-server') !== null) {
            $importParams['--url'] = $input->getOption('source-server');
        }

        // Run the 'import' command.
        $importReturn = $importCommand->run(new ArrayInput($importParams), $output);
        if (0 === $importReturn) {
            $output->writeln('<info>Operation completed.</info>');
        } else {
            $output->writeln('<error>Operation failed.</error>');
        }
        return $importReturn;
    }
}
