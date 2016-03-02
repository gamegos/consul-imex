<?php
namespace Gamegos\ConsulImex;

/* Imports from symfony/console */
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/* Imports from Guzzle */
use GuzzleHttp\Client;

/**
 * Consul Export Command
 * @author Safak Ozpinar <safak@gamegos.com>
 */
class ExportCommand extends Command
{
    /**
     * Current base URL of key-value API.
     * @var string
     */
    protected $baseUrl = '';

    /**
     * Key prefix for current operation.
     * @var string
     */
    protected $prefix = '';

    /**
     * Current console output handler.
     * @var OutputInterface
     */
    protected $output;

    /**
     * Default API URL
     * @var string
     */
    const DEFAULT_URL = 'http://localhost:8500';

    /**
     * Key-value endpoint
     * @var string
     */
    const ENDPOINT    = '/v1/kv/';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('export');
        $this->setDescription('Exports data from Consul key-value service.');

        $this->addArgument('file', InputArgument::REQUIRED, 'Output data file.');
        $this->addOption('url', 'u', InputOption::VALUE_OPTIONAL, 'Consul server url.', self::DEFAULT_URL);
        $this->addOption('prefix', 'p', InputOption::VALUE_OPTIONAL, 'Path prefix.', '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getOption('url');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ExportException(sprintf('Invalid url (%s).', $url));
        }
        $this->baseUrl = $url . self::ENDPOINT;

        $this->prefix = trim($input->getOption('prefix'), '/');
        if ('' !== $this->prefix) {
            $this->prefix .= '/';
        }

        $file   = $input->getArgument('file');
        $handle = @ fopen($file, 'wb');
        if (false === $handle) {
            throw new ExportException(sprintf('Cannot open file for writing (%s).', $file));
        }

        $this->output = $output;

        $data = $this->process();
        if (!fwrite($handle, json_encode($data, JSON_PRETTY_PRINT))) {
            throw new ExportException(sprintf('Cannot write file (%s).', $file));
        }

        $this->output = null;
    }

    /**
     * Process the operation.
     */
    protected function process()
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
        $uri = $this->baseUrl . $this->prefix . '?keys';
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $response = (new Client())->get($uri, ['exceptions' => false]);
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

        $prefixLength = strlen($this->prefix);

        return array_filter(
            array_map(
                function ($path) use ($prefixLength) {
                    return substr($path, $prefixLength);
                },
                $keys
            ),
            'strlen'
        );
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

        $this->output->write("Fetch key: <comment>{$path}</comment> ... ");

        $uri = $this->baseUrl. $this->prefix . $path . '?raw';
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $response = (new Client())->get($uri, ['exceptions' => false]);
        $status   = $response->getStatusCode() == 200 ? '<info>OK</info>' : '<error>Fail</error>';

        $container[basename($path)] = $response->getBody()->getContents();

        $this->output->writeln($status);
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
