<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventListener;

use ParadiseSecurity\Bundle\SapientBundle\Checker\BadStateCheckerInterface;
use ParadiseSecurity\Bundle\SapientBundle\Event\SapientEventTrait;
use ParadiseSecurity\Bundle\SapientBundle\Handler\StateHandlerInterface;
use ParadiseSecurity\Bundle\SapientBundle\Helper\HttpHelperInterface;
use ParadiseSecurity\Bundle\SapientBundle\Provider\ClientNameProviderInterface;
use ParadiseSecurity\Bundle\SapientBundle\Sapient\SapientInterface;
use ParadiseSecurity\Bundle\SapientBundle\Utility\Utility;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_key_exists;

class ClientEventListener
{
    use SapientEventTrait;

    public function __construct(
        protected SapientInterface $sapientClient,
        protected BadStateCheckerInterface $badStateChecker,
        protected StateHandlerInterface $stateHandler,
        protected ClientNameProviderInterface $clientNameProvider,
        protected HttpHelperInterface $httpHelper,
        protected LoggerInterface $logger,
    ) {
    }

    protected function getClientIdentifier(Request|Response|MessageInterface $message, string $serviceName): string
    {
        if ($message instanceof ResponseInterface) {
            return $this->getServerHeader($message);
        }

        return $this->matchClient($message, $serviceName);
    }

    private function matchClient(Request|Response|MessageInterface $message, string $serviceName): string
    {
        $clients = $this->sapientClient->getClients();

        foreach ($clients as $identifier => $config) {
            if (!$this->isGuzzleAliasValid($config, $serviceName)) {
                continue;
            }
            if (!$this->isHostValid($message, $config)) {
                continue;
            }
            if (!$this->isPathValid($message, $config)) {
                continue;
            }
            return $identifier;
        }
        return '';
    }

    protected function isResponseValid(Response|MessageInterface $message): bool
    {
        $identifier = $this->getServerHeader($message);

        return array_key_exists($identifier, $this->sapientClient->getClients());
    }

    protected function isRequestValid(Request|MessageInterface $message, string $serviceName): bool
    {
        return ($this->matchClient($message, $serviceName) !== '');
    }

    private function isGuzzleAliasValid(array $config, string $serviceName): bool
    {
        if (!isset($config['guzzle_alias'])) {
            return false;
        }

        return ($config['guzzle_alias'] === $serviceName);
    }

    private function isHostValid(Request|MessageInterface $message, array $config): bool
    {
        if (!isset($config['host'])) {
            return false;
        }

        $hostA = Utility::sanitizeHost($config['host']);
        $hostB = $this->httpHelper->getHost($message);

        return ($hostA === $hostB);
    }

    private function isPathValid(Request|MessageInterface $message, array $config): bool
    {
        if (!isset($config['endpoints'])) {
            return false;
        }

        $path = $this->httpHelper->getPath($message);

        foreach ($config['endpoints'] as $endpoint) {
            $endpoint = Utility::sanitizePath($endpoint);
            if (str_contains($path, $endpoint)) {
                return true;
            }
        }
        return false;
    }
}
