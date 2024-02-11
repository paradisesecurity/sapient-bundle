<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventSubscriber;

use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function sprintf;

class VerifyRequestSubscriber extends ServerEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['verifyRequest', -110],
        ];
    }

    /**
     * Verifies a Client Signature is valid from a Symfony Request.
     */
    public function verifyRequest(KernelEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isRequestValid($request)) {
            return;
        }

        $alreadyVerified = [PayloadInterface::STATE_VERIFIED];
        if (!$this->badStateChecker->check($request, $this->sapientServer, $alreadyVerified)) {
            return;
        }

        if ($this->stateHandler->canVerify($request)) {
            $identifier = $this->getClientIdentifier($request);

            $client = $this->clientNameProvider->provide($this->sapientServer->getClients(), $identifier);

            $psrRequest = $this->httpHelper->createPsrRequest($request);
            $psrRequest = $this->verifyPsrRequest($psrRequest, $client);
            $this->httpHelper->initializeRequest($request, $psrRequest);
        }
    }

    private function verifyPsrRequest(RequestInterface $request, string $client): RequestInterface
    {
        try {
            $request = $this->sapientServer->verifyAndReturnSignedPsrRequest(
                $request,
                $client
            );

            $request = $this->stateHandler->verify($request);
        } catch (\Exception $exception) {
            $logMessage = sprintf(
                'Could not verify `%s` client\'s signature on `%s` servers\'s request.',
                $client,
                $this->sapientServer->getName(),
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
