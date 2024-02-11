<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventSubscriber;

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
use Symfony\Component\HttpKernel\Event\KernelEvent;

use function array_key_exists;

class ServerEventSubscriber
{
    use SapientEventTrait;

    public function __construct(
        protected SapientInterface $sapientServer,
        protected BadStateCheckerInterface $badStateChecker,
        protected StateHandlerInterface $stateHandler,
        protected ClientNameProviderInterface $clientNameProvider,
        protected HttpHelperInterface $httpHelper,
        protected LoggerInterface $logger,
    ) {
    }

    protected function validateRequestBeforeResponse(KernelEvent $event): bool
    {
        $request = $event->getRequest();

        if (!$this->isRequestValid($request)) {
            return false;
        }

        if (!$this->badStateChecker->check($request, $this->sapientServer)) {
            return false;
        }

        return true;
    }

    protected function setSymfonyResponse(KernelEvent $event, ResponseInterface $response): void
    {
        $response = $this->httpHelper->createSymfonyResponse($response);

        $event->setResponse($response);
    }

    protected function getClientIdentifier(Request|Response|MessageInterface $message): string
    {
        return $this->matchClient($message);
    }

    private function matchClient(Request|Response|MessageInterface $message): string
    {
        $clients = $this->sapientServer->getClients();

        $identifier = $this->getClientHeader($message);

        if (array_key_exists($identifier, $clients)) {
            return $identifier;
        }

        return '';
    }

    protected function isResponseValid(Response|MessageInterface $message): bool
    {
        return ($this->sapientServer->getIdentifier() === $this->getServerHeader($message));
    }

    protected function isRequestValid(Request|MessageInterface $message): bool
    {
        if (!$this->isHostValid($message)) {
            return false;
        }
        if (!$this->isPathValid($message)) {
            return false;
        }
        if ($this->matchClient($message) === '') {
            return false;
        }
        return true;
    }

    private function isHostValid(Request|MessageInterface $message): bool
    {
        $hostA = Utility::sanitizeHost($this->sapientServer->getHost());
        $hostB = $this->httpHelper->getHost($message);

        return ($hostA === $hostB);
    }

    private function isPathValid(Request|MessageInterface $message): bool
    {
        $path = $this->httpHelper->getPath($message);

        foreach ($this->sapientServer->getAccessPoints() as $endpoint) {
            $endpoint = Utility::sanitizePath($endpoint);

            if (str_contains($path, $endpoint)) {
                return true;
            }
        }
        return false;
    }
}
