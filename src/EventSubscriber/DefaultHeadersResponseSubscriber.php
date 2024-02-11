<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventSubscriber;

use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DefaultHeadersResponseSubscriber extends ServerEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['addDefaultHeadersToPsrResponse', -45],
            KernelEvents::RESPONSE => ['addDefaultHeadersToHttpFoundationResponse', -50],
        ];
    }

    public function addDefaultHeadersToHttpFoundationResponse(KernelEvent $event): void
    {
        if (!$this->validateRequestBeforeResponse($event)) {
            return;
        }

        $psrResponse = $this->httpHelper->createPsrResponse($event->getResponse());

        $this->addHeadersToResponse($event, $psrResponse);
    }

    public function addDefaultHeadersToPsrResponse(KernelEvent $event): void
    {
        if (!$this->validateRequestBeforeResponse($event)) {
            return;
        }

        $response = $event->getControllerResult();

        if ($response instanceof Response) {
            $response = $this->httpHelper->createPsrResponse($response);
        }

        if ($response instanceof ResponseInterface) {
            $this->addHeadersToResponse($event, $response);
        }
    }

    /**
     * Adds the default headers from a Symfony Request to Psr Response.
     */
    private function addHeadersToResponse(KernelEvent $event, ResponseInterface $response): void
    {
        $response = $this->setPsrResponseHeaders($response);

        if (!$this->isResponseValid($response)) {
            $this->setSymfonyResponse($event, $response);
            return;
        }

        $alreadyCreated = [PayloadInterface::STATE_NEW];
        if (!$this->badStateChecker->check($response, $this->sapientServer, $alreadyCreated)) {
            return;
        }

        $response = $this->stateHandler->create($response);

        $this->setSymfonyResponse($event, $response);
    }

    private function setPsrResponseHeaders(ResponseInterface $response): ResponseInterface
    {
        if (!$this->isStateHeaderSet($response)) {
            $response = $this->setStateHeader($response, PayloadInterface::STATE_WAITING);
        }

        if (!$this->isServerHeaderSet($response)) {
            $response = $this->setServerHeader($response, $this->sapientServer->getIdentifier());
        }

        return $response;
    }
}
