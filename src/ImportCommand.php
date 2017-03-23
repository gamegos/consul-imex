<?php
namespace Gamegos\ConsulImex;

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

        $this->import($data);
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
                    $this->setKey($path);
                }
                $this->import($value, $path);
            } else {
                $this->setKey($path, $value);
            }
        }
    }

    /**
     * Create/update a key under current base URL.
     * @param string $key
     * @param string $value
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
        $this->getOutput()->writeln($response->getStatusCode() == 200 ? '<info>OK</info>' : '<error>Fail</error>', OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * Check if a key exists.
     * @param  string $key
     * @return bool
     */
    protected function keyExists($key, $isRelative = false)
    {
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $response = $this->getHttpClient()->get($this->createUri($key), ['exceptions' => false]);
        return $response->getStatusCode() == 200;
    }
}
