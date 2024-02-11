<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventListener;

use ParadiseSecurity\Bundle\GuzzleBundle\Event\PreTransactionEvent;
use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use Psr\Http\Message\RequestInterface;

use function sprintf;

class SealRequestEventListener extends ClientEventListener
{
    /**
     * Seals a Psr Request for the client before it is sent to the server.
     */
    public function onPreTransaction(PreTransactionEvent $event): void
    {
        $request = $event->getTransaction();
        $serviceName = $event->getServiceName();

        if (!$this->isRequestValid($request, $serviceName)) {
            $event->stopPropagation();
            return;
        }

        $alreadySealed = [PayloadInterface::STATE_SEALED];
        if (!$this->badStateChecker->check($request, $this->sapientClient, $alreadySealed)) {
            return;
        }

        if ($this->stateHandler->canSeal($request)) {
            $identifier = $this->getClientIdentifier($request, $serviceName);

            $client = $this->clientNameProvider->provide($this->sapientClient->getClients(), $identifier);

            $request = $this->sealRequest($request, $client);
            $event->setTransaction($request);
        }
    }

    private function sealRequest(RequestInterface $request, string $client): RequestInterface
    {
        try {
            $request = $this->sapientClient->sealAndReturnPsrRequest($request, $client);

            $request = $this->stateHandler->seal($request);
        } catch (\Exception $exception) {
            $logMessage = sprintf(
                'Could not seal `%s` client\'s request for server on host `%s`.',
                $this->sapientClient->getName(),
                $this->httpHelper->getHost($request),
            );

            $request = $this->logMessageAndSetFailedState(
                $exception,
                $logMessage,
                $this->sapientClient->getFailState(),
                $request,
            );
        }

        return $request;
    }
}
