<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventListener;

use ParadiseSecurity\Bundle\GuzzleBundle\Event\PostTransactionEvent;
use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;

class UnsealResponseEventListener extends ClientEventListener
{
    /**
     * Unseals a Psr Response from the server after it is received by the client.
     */
    public function onPostTransaction(PostTransactionEvent $event)
    {
        $response = $event->getTransaction();
        $serviceName = $event->getServiceName();

        if (!$this->isResponseValid($response, $serviceName)) {
            $event->stopPropagation();
            return;
        }

        $alreadyUnsealed = [PayloadInterface::STATE_UNSEALED];
        if (!$this->badStateChecker->check($response, $this->sapientClient, $alreadyUnsealed)) {
            return;
        }

        if ($this->stateHandler->canUnseal($response)) {
            $identifier = $this->getClientIdentifier($response, $serviceName);

            $client = $this->clientNameProvider->provide($this->sapientClient->getClients(), $identifier);

            $response = $this->unSealResponse($response, $client);
            $event->setTransaction($response);
        }
    }

    private function unSealResponse(ResponseInterface $response, string $client): ResponseInterface
    {
        try {
            $response = $this->sapientClient->unsealAndReturnPsrResponse($response);

            $response = $this->stateHandler->unseal($response);
        } catch (\Exception $exception) {
            $logMessage = sprintf(
                'Could not unseal `%s` server\'s response for `%s` client.',
                $client,
                $this->sapientClient->getName(),
            );

            $response = $this->logMessageAndSetFailedState(
                $exception,
                $logMessage,
                $this->sapientClient->getFailState(),
                $response,
            );
        }

        return $response;
    }
}
