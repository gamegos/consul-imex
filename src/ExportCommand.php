<?php
namespace Gamegos\ConsulImex;

/* Imports from symfony/console */
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Consul Export
 * @author Safak Ozpinar <safak@gamegos.com>
 */
class ExportCommand extends AbstractCommand
{
    /**
     * Construct.
     */
    public function __construct()
    {
        parent::__construct('export');
        $this->setDescription('Exports data from Consul key-value service.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data   = $this->export();
        $file   = $input->getArgument('file');
        $handle = @ fopen($file, 'wb');
        if (false === $handle) {
            throw new ExportException(sprintf('Cannot open file for writing (%s).', $file));
        }
        if (!fwrite($handle, json_encode($data, JSON_PRETTY_PRINT))) {
            throw new ExportException(sprintf('Cannot write file (%s).', $file));
        }
    }

    /**
     * Export values into a nested array.
     * @return array
     */
    protected function export()
    {
        $keys = $this->getKeys();
        $data = [];
        foreach ($keys as $path) {
            $this->fetchValue($data, $path);
        }
        return $data;
    }

    /**
     * Get the keys under current prefix from Consul.
     * @throws \Gamegos\ConsulImex\ExportException
     * @return array
     */
    protected function getKeys()
    {
        $uri = $this->createUri('?keys');
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $response = $this->getHttpClient()->get($uri, ['exceptions' => false]);
        if ($response->getStatusCode() != 200) {
            throw new ExportException(
                sprintf('Consul API request returned %d (%s).', $response->getStatusCode(), $uri)
            );
        }

        $contents = $response->getBody()->getContents();
        $keys     = @ json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE
            || !is_array($keys)
            || count(array_filter($keys, 'is_string')) != count($keys)
        ) {
            throw new ExportException(sprintf('Unexpected Consul API response format (%s).', $uri));
        }

        return array_filter(array_map([$this, 'getRelativeKey'], $keys), 'strlen');
    }

    /**
     * Fetch a value from Consul into a buffer with reccursive index.
     * @param array $buffer Buffer that the value will be added in.
     * @param string $path Path of a key that is relative to current prefix.
     */
    protected function fetchValue(array & $buffer, $path)
    {
        $container = & $this->createContainer($buffer, $path);
        if (preg_match('#/$#', $path)) {
            return;
        }

        $this->getOutput()->write("Fetch key: <comment>{$this->getFullKey($path)}</comment> ... ", false, OutputInterface::VERBOSITY_VERBOSE);

        $uri = $this->createUri($path . '?raw');
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $response = $this->getHttpClient()->get($uri, ['exceptions' => false]);
        $status   = $response->getStatusCode() == 200 ? '<info>OK</info>' : '<error>Fail</error>';

        $container[basename($path)] = $response->getBody()->getContents();

        $this->getOutput()->writeln($status, OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * Create a container with parent containers in a buffer for a path.
     * Returns reference to the created container.
     * @param  array $buffer
     * @param  string $path
     * @return array
     */
    protected function & createContainer(array & $buffer, $path)
    {
        $path  = trim($path, '/');
        $parts = explode('/', $path);
        $array = & $buffer;
        for ($i = 0; $i < count($parts) - 1; $i++) {
            if (!array_key_exists($parts[$i], $array)) {
                $array[$parts[$i]] = [];
            }
            $array = & $array[$parts[$i]];
        }
        return $array;
    }
}
