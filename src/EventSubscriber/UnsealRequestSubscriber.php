<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventSubscriber;

use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function sprintf;

class UnsealRequestSubscriber extends ServerEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['unsealRequest', 100],
        ];
    }

    /**
     * Unseals a Symfony Request for the server after it is received from the client.
     */
    public function unsealRequest(KernelEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isRequestValid($request)) {
            return;
        }

        $alreadyUnsealed = [PayloadInterface::STATE_UNSEALED];
        if (!$this->badStateChecker->check($request, $this->sapientServer, $alreadyUnsealed)) {
            return;
        }

        if ($this->stateHandler->canUnseal($request)) {
            $identifier = $this->getClientIdentifier($request);

            $client = $this->clientNameProvider->provide($this->sapientServer->getClients(), $identifier);

            $psrRequest = $this->httpHelper->createPsrRequest($request);
            $psrRequest = $this->unSealPsrRequest($psrRequest, $client);
            $this->httpHelper->initializeRequest($request, $psrRequest);
        }
    }

    private function unSealPsrRequest(RequestInterface $request, string $client): RequestInterface
    {
        try {
            $request = $this->sapientServer->unsealAndReturnPsrRequest($request);

            $request = $this->stateHandler->unseal($request);
        } catch (\Exception $exception) {
            $logMessage = sprintf(
                'Could not unseal `%s` servers\'s request from client on host `%s`.',
                $this->sapientServer->getName(),
                $client,
            );

            $request = $this->logMessageAndSetFailedState(
                $exception,
                $logMessage,
                $this->sapientServer->getFailState(),
                $request,
            );
        }

        return $request;
    }
}
