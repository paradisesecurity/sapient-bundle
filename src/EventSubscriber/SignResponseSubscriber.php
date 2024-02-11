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

class SignResponseSubscriber extends ServerEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['signPsrResponse', -110],
            KernelEvents::RESPONSE => ['signHttpFoundationResponse', -100],
        ];
    }

    public function signHttpFoundationResponse(KernelEvent $event): void
    {
        if (!$this->validateRequestBeforeResponse($event)) {
            return;
        }

        $psrResponse = $this->httpHelper->createPsrResponse($event->getResponse());

        $this->signResponse($event, $psrResponse);
    }

    public function signPsrResponse(KernelEvent $event): void
    {
        if (!$this->validateRequestBeforeResponse($event)) {
            return;
        }

        $response = $event->getControllerResult();

        if ($response instanceof Response) {
            $response = $this->httpHelper->createPsrResponse($response);
        }

        if ($response instanceof ResponseInterface) {
            $this->signResponse($event, $response);
        }
    }

    /**
     * Signs a Psr Response and adds a Signer Header from the server for the client.
     */
    private function signResponse(KernelEvent $event, ResponseInterface $response): void
    {
        if (!$this->isResponseValid($response)) {
            $this->setSymfonyResponse($event, $response);
            return;
        }

        $alreadySigned = [PayloadInterface::STATE_SIGNED];
        if (!$this->badStateChecker->check($response, $this->sapientServer, $alreadySigned)) {
            return;
        }

        if ($this->stateHandler->canSign($response)) {
            $request = $event->getRequest();
            $identifier = $this->getClientIdentifier($request);

            $client = $this->clientNameProvider->provide($this->sapientServer->getClients(), $identifier);

            $response = $this->doSignResponse($response, $client);
            $this->setSymfonyResponse($event, $response);
        }
    }

    private function doSignResponse(ResponseInterface $response, string $client): ResponseInterface
    {
        try {
            $response = $this->sapientServer->signAndReturnPsrResponse($response);

            $response = $this->stateHandler->sign($response);
        } catch (\Exception $exception) {
            $logMessage = sprintf(
                'Could not sign `%s` servers\'s response for client with `%s` host.',
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
