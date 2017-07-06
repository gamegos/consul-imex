<?php
namespace Gamegos\ConsulImex\Command;

/* Imports from symfony/console */
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;

/* Imports from Guzzle */
use GuzzleHttp\Client;

/**
 * Base Class for Commands
 * @author Safak Ozpinar <safak@gamegos.com>
 */
abstract class AbstractCommand extends Command
{
    /**
     * Default API URL
     * @var string
     */
    const DEFAULT_URL = 'http://localhost:8500';

    /**
     * Key-value endpoint
     * @var string
     */
    const ENDPOINT = '/v1/kv/';

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
     * HTTP Client
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * Current console output handler.
     * @var OutputInterface
     */
    protected $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument('file', InputArgument::REQUIRED);
        $this->addOption('url', 'u', InputOption::VALUE_REQUIRED, 'Consul server url.', self::DEFAULT_URL);
        $this->addOption('prefix', 'p', InputOption::VALUE_REQUIRED, 'Path prefix.', '');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->healthCheck($input);

        $this->prefix = trim($input->getOption('prefix'), '/');
        if ('' !== $this->prefix) {
            $this->prefix .= '/';
        }

        $this->baseUrl = $input->getOption('url') . self::ENDPOINT;
        $this->output  = $output;
    }

    /**
     * Get HTTP client to make a request.
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new Client();
        }
        return $this->httpClient;
    }

    /**
     * Get current console output handler.
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    protected function getOutput()
    {
        return $this->output;
    }

    /**
     * Create absolute URI for the specified relative URI.
     * @param  string $relativeUri
     * @return string
     */
    protected function createUri($relativeUri)
    {
        return $this->baseUrl . $this->prefix . $relativeUri;
    }

    /**
     * Health check.
     * @param  \Symfony\Component\Console\Input\InputInterface $input
     * @throws \Symfony\Component\Console\Exception\RuntimeException
     */
    public function healthCheck(InputInterface $input)
    {
        $server   = $input->getOption('url');
        $response = $this->getHttpClient()->get($server . '/v1/health/service/consul', ['exceptions' => false]);
        if ($response->getStatusCode() != 200) {
            throw new RuntimeException("Consul server connection failed for '{$server}'.");
        }
    }

    /**
     * Crop prefix of a full key.
     * @param  string $fullKey
     * @return string
     */
    public function getRelativeKey($fullKey)
    {
        return substr($fullKey, strlen($this->prefix));
    }

    /**
     * Get full key of a relative key.
     * @param  string $key
     * @return string
     */
    public function getFullKey($key)
    {
        return $this->prefix . $key;
    }
}
