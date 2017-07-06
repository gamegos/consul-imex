<?php
namespace Gamegos\ConsulImex\Command;

/* Imports from symfony/console */
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Consul Import
 * @author Safak Ozpinar <safak@gamegos.com>
 */
class ImportCommand extends AbstractCommand
{
    /**
     * Number of stored keys.
     * @var integer
     */
    protected $keyCount = 0;

    /**
     * Number of created directories.
     * @var integer
     */
    protected $dirCount = 0;

    /**
     * Construct.
     */
    public function __construct()
    {
        parent::__construct('import');
        $this->setDescription('Imports data from a file to Consul key-value service.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file   = $input->getArgument('file');
        $handle = @ fopen($file, 'rb');
        if (false === $handle) {
            throw new ImportException(sprintf('Cannot open file for reading (%s).', $file));
        }

        $json = @ stream_get_contents($handle);
        if (false === $json) {
            throw new ImportException(sprintf('Cannot read file (%s).', $file));
        }

        $data = @ json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ImportException(json_last_error_msg());
        }
        if (!is_array($data)) {
            throw new ImportException('Invalid JSON, expected an object or array as the root element.');
        }

        $this->keyCount = 0;
        $this->dirCount = 0;

        $this->import($data);

        $this->getOutput()->write(sprintf(
            '<options=bold>%d</> key%s stored.',
            $this->keyCount,
            $this->keyCount == 1 ? ' is' : 's are'
        ));
        if ($this->dirCount) {
            $this->getOutput()->write(sprintf(
                ' (%d new director%s created.)',
                $this->dirCount,
                $this->dirCount == 1 ? 'y is' : 'ies are'
            ));
        }
        $this->getOutput()->writeln('');
    }

    /**
     * Import nested array under a prefix.
     * @param array $data
     * @param string $prefix
     */
    protected function import(array $data, $prefix = '')
    {
        foreach ($data as $key => $value) {
            $path = $prefix . trim($key, '/');
            if (is_array($value)) {
                $path .= '/';
                if (!$this->keyExists($path)) {
                    $this->dirCount += $this->setKey($path);
                }
                $this->import($value, $path);
            } else {
                $this->keyCount += $this->setKey($path, $value);
            }
        }
    }

    /**
     * Create/update a key under current base URL.
     * @param  string $key
     * @param  string $value
     * @return bool
     */
    protected function setKey($key, $value = null)
    {
        $this->getOutput()->write("Set key: <comment>{$this->getFullKey($key)}</comment> ... ", false, OutputInterface::VERBOSITY_VERBOSE);
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $response = $this->getHttpClient()->put(
            $this->createUri($key),
            [
                'body'       => $value,
                'exceptions' => false,
            ]
        );

        if ($response->getStatusCode() == 200) {
            $this->getOutput()->writeln('<info>OK</info>', OutputInterface::VERBOSITY_VERBOSE);
            return true;
        }

        $this->getOutput()->writeln('<error>Fail</error>', OutputInterface::VERBOSITY_VERBOSE);
        return false;
    }

    /**
     * Check if a key exists.
     * @param  string $key
     * @param  bool $isRelative
     * @return bool
     */
    protected function keyExists($key, $isRelative = false)
    {
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $response = $this->getHttpClient()->get($this->createUri($key), ['exceptions' => false]);
        return $response->getStatusCode() == 200;
    }
}
