<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventSubscriber;

use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function sprintf;

class SealResponseSubscriber extends ServerEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['sealPsrResponse', -100],
            KernelEvents::RESPONSE => ['sealHttpFoundationResponse', -110],
        ];
    }

    public function sealHttpFoundationResponse(KernelEvent $event): void
    {
        if (!$this->validateRequestBeforeResponse($event)) {
            return;
        }

        $psrResponse = $this->httpHelper->createPsrResponse($event->getResponse());

        $this->sealResponse($event, $psrResponse);
    }

    public function sealPsrResponse(KernelEvent $event): void
    {
        if (!$this->validateRequestBeforeResponse($event)) {
            return;
        }

        $response = $event->getControllerResult();

        if ($response instanceof Response) {
            $response = $this->httpHelper->createPsrResponse($response);
        }

        if ($response instanceof ResponseInterface) {
            $this->sealResponse($event, $response);
        }
    }

    /**
     * Seals a Psr Response from the server before it is sent to the client.
     */
    private function sealResponse(KernelEvent $event, ResponseInterface $response): void
    {
        if (!$this->isResponseValid($response)) {
            $this->setSymfonyResponse($event, $response);
            return;
        }

        $alreadySealed = [PayloadInterface::STATE_SEALED];
        if (!$this->badStateChecker->check($response, $this->sapientServer, $alreadySealed)) {
            return;
        }

        if ($this->stateHandler->canSeal($response)) {
            $request = $event->getRequest();
            $identifier = $this->getClientIdentifier($request);

            $client = $this->clientNameProvider->provide($this->sapientServer->getClients(), $identifier);

            $response = $this->doSealPsrResponse($response, $client);
            $this->setSymfonyResponse($event, $response);
        }
    }

    private function doSealPsrResponse(ResponseInterface $response, string $client): ResponseInterface
    {
        try {
            $response = $this->sapientServer->sealAndReturnPsrResponse($response, $client);

            $response = $this->stateHandler->seal($response);
        } catch (\Exception $exception) {
            $logMessage = sprintf(
                'Could not seal `%s` servers\'s response for `%s` client.',
                $this->sapientServer->getName(),
                $client,
            );

            $response = $this->logMessageAndSetFailedState(
                $exception,
                $logMessage,
                $this->sapientServer->getFailState(),
                $response,
            );
        }

        return $response;
    }
}
