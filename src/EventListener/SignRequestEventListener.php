<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventListener;

use ParadiseSecurity\Bundle\GuzzleBundle\Event\PreTransactionEvent;
use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use Psr\Http\Message\RequestInterface;

use function sprintf;

final class SignRequestEventListener extends ClientEventListener
{
    /**
     * Signs a Psr Request for the client before it is sent to the server.
     */
    public function onPreTransaction(PreTransactionEvent $event): void
    {
        $request = $event->getTransaction();
        $serviceName = $event->getServiceName();

        if (!$this->isRequestValid($request, $serviceName)) {
            $event->stopPropagation();
            return;
        }

        $alreadySigned = [PayloadInterface::STATE_SIGNED];
        if (!$this->badStateChecker->check($request, $this->sapientClient, $alreadySigned)) {
            return;
        }

        if ($this->stateHandler->canSign($request)) {
            $request = $this->signRequest($request);
            $event->setTransaction($request);
        }
    }

    private function signRequest(RequestInterface $request): RequestInterface
    {
        try {
            $request = $this->sapientClient->signAndReturnPsrRequest($request);

            $request = $this->stateHandler->sign($request);
        } catch (\Exception $exception) {
            $logMessage = sprintf(
                'Could not sign `%s` client\'s request for server on host `%s`.',
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
