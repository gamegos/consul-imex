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
 * Consul Import Command
 * @author Safak Ozpinar <safak@gamegos.com>
 */
class ImportCommand extends Command
{
    /**
     * Current base URL of key-value API.
     * @var string
     */
    protected $baseUrl = '';

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
        $this->setName('import');
        $this->setDescription('Imports data from a file to Consul key-value service.');

        $this->addArgument('file', InputArgument::REQUIRED, 'Input data file.');
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
            throw new ImportException(sprintf('Invalid url (%s).', $url));
        }
        $this->baseUrl = $url . self::ENDPOINT;

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

        $prefix = trim($input->getOption('prefix'), '/');
        if ('' !== $prefix) {
            $prefix .= '/';
        }

        $this->output = $output;

        $this->process($data, $prefix);

        $this->output = null;
    }

    /**
     * Process the operation.
     * @param array $data
     * @param string $prefix
     */
    protected function process(array $data, $prefix)
    {
        foreach ($data as $key => $value) {
            $key = $prefix . trim($key, '/');
            if (is_array($value)) {
                $key .= '/';
                if (!$this->keyExists($key)) {
                    $this->setKey($key);
                }
                $this->process($value, $key);
            } else {
                $this->setKey($key, $value);
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
        $this->output->write("Set key: <comment>{$key}</comment> ... ");
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $response = (new Client())->put($this->baseUrl . $key, ['body' => $value, 'exceptions' => false]);
        $status   = $response->getStatusCode() == 200 ? '<info>OK</info>' : '<error>Fail</error>';
        $this->output->writeln($status);
    }

    /**
     * Check if a key exists.
     * @param  string $key
     * @return bool
     */
    protected function keyExists($key)
    {
        /* @var $response \Psr\Http\Message\ResponseInterface */
        $response = (new Client())->get($this->baseUrl . $key, ['exceptions' => false]);
        return $response->getStatusCode() == 200;
    }
}
