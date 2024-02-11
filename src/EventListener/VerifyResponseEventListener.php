<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventListener;

use GuzzleHttp\Psr7\Response;
use ParadiseSecurity\Bundle\GuzzleBundle\Event\PostTransactionEvent;
use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;

class VerifyResponseEventListener extends ClientEventListener
{
    /**
     * Verifies a Server Signature is valid from a Psr Response.
     */
    public function onPostTransaction(PostTransactionEvent $event)
    {
        $response = $event->getTransaction();
        $serviceName = $event->getServiceName();

        if (!$this->isResponseValid($response, $serviceName)) {
            $event->stopPropagation();
            return;
        }

        $alreadyVerified = [PayloadInterface::STATE_VERIFIED];
        if (!$this->badStateChecker->check($response, $this->sapientClient, $alreadyVerified)) {
            return;
        }

        if ($this->stateHandler->canVerify($response)) {
            $identifier = $this->getClientIdentifier($response, $serviceName);

            $client = $this->clientNameProvider->provide($this->sapientClient->getClients(), $identifier);

            $response = $this->verifyResponse($response, $client);
            $response->getBody()->rewind();
            $event->setTransaction($response);
        }
    }

    private function verifyResponse(ResponseInterface $response, string $client): ResponseInterface
    {
        try {
            $response = $this->sapientClient->verifyAndReturnSignedPsrResponse($response, $client);

            $response = $this->stateHandler->verify($response);
        } catch (\Exception $exception) {
            $logMessage = sprintf(
                'Could not verify `%s` server\'s signature on response for `%s` client.',
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
