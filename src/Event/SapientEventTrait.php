<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Event;

use GuzzleHttp\Psr7\Utils;
use ParadiseSecurity\Bundle\SapientBundle\SapientHeaders;
use ParadiseSecurity\Bundle\SapientBundle\Sapient\SapientInterface;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function is_string;
use function json_encode;
use function property_exists;

trait SapientEventTrait
{
    protected function setServerHeader(
        Request|Response|MessageInterface $message,
        string $content,
    ): Request|Response|MessageInterface {
        return $this->httpHelper->replaceHeaders($message, SapientHeaders::HEADER_SERVER_IDENTIFIER, $content);
    }

    protected function setClientHeader(
        Request|Response|MessageInterface $message,
        string $content,
    ): Request|Response|MessageInterface {
        return $this->httpHelper->replaceHeaders($message, SapientHeaders::HEADER_CLIENT_IDENTIFIER, $content);
    }

    protected function setStateHeader(
        Request|Response|MessageInterface $message,
        string $content,
    ): Request|Response|MessageInterface {
        return $this->httpHelper->replaceHeaders($message, SapientHeaders::HEADER_STATE, $content);
    }

    protected function isServerHeaderSet(Request|Response|MessageInterface $message): bool
    {
        return $this->isHeaderSet($message, SapientHeaders::HEADER_SERVER_IDENTIFIER);
    }

    protected function isClientHeaderSet(Request|Response|MessageInterface $message): bool
    {
        return $this->isHeaderSet($message, SapientHeaders::HEADER_CLIENT_IDENTIFIER);
    }

    protected function isStateHeaderSet(Request|Response|MessageInterface $message): bool
    {
        return $this->isHeaderSet($message, SapientHeaders::HEADER_STATE);
    }

    protected function isHeaderSet(Request|Response|MessageInterface $message, string $header): bool
    {
        $headers = $this->httpHelper->getHeader($message, $header);

        if (empty($headers)) {
            return false;
        }

        return true;
    }

    protected function getStateHeader(Request|Response|MessageInterface $message): string
    {
        return $this->getSingleHeader($message, SapientHeaders::HEADER_STATE);
    }

    protected function getClientHeader(Request|Response|MessageInterface $message): string
    {
        return $this->getSingleHeader($message, SapientHeaders::HEADER_CLIENT_IDENTIFIER);
    }

    protected function getServerHeader(Request|Response|MessageInterface $message): string
    {
        return $this->getSingleHeader($message, SapientHeaders::HEADER_SERVER_IDENTIFIER);
    }

    protected function getSingleHeader(
        Request|Response|MessageInterface $message,
        string $header,
    ): string {
        $headers = $this->httpHelper->getHeader($message, $header);

        if (empty($headers)) {
            return '';
        }

        $headers = $headers[0];

        if (!is_string($headers)) {
            return '';
        }

        return $headers;
    }

    protected function getSapient(): SapientInterface
    {
        if (property_exists($this, 'sapientClient')) {
            return $this->sapientClient;
        }

        if (property_exists($this, 'sapientServer')) {
            return $this->sapientServer;
        }
    }

    protected function setFailedState(
        string $failState,
        Request|Response|MessageInterface $message,
    ): Request|Response|MessageInterface {
        $message = $this->stateHandler->fail($message);

        return $this->markContentForbidden($message, $failState);
    }

    protected function logMessageAndSetFailedState(
        \Exception $exception,
        string $logMessage,
        string $failState,
        Request|Response|MessageInterface $message,
    ): Request|Response|MessageInterface {
        $this->logger->warning(
            $logMessage,
            ['exception' => $exception, 'message' => $exception->getMessage()],
        );

        return $this->setFailedState($failState, $message);
    }

    protected function markContentForbidden(
        Request|Response|MessageInterface $message,
        string $failState,
    ): Request|Response|MessageInterface {
        if ($failState === 'open') {
            return $message;
        }

        if ($message instanceof Request) {
            $message = $this->httpHelper->createPsrRequest($message);
        }

        if ($message instanceof Response) {
            $message = $this->httpHelper->createPsrResponse($message);
        }

        $stream = Utils::streamFor($this->getErrorResponse());

        $message = $message->withBody($stream);

        if ($message instanceof Request) {
            $message = $this->httpHelper->createSymfonyRequest($message);
        }

        if ($message instanceof Response) {
            $message = $this->httpHelper->createSymfonyResponse($message);
        }

        return $message;
    }

    protected function getErrorResponse(): string
    {
        $errorMessage = [
            'version' => $this->getSapient()->getVersion(),
            'datetime' => (new \DateTime())->format(\DateTime::ATOM),
            'status' => 'ERROR',
            'message' => 'We are unable to display this content.',
        ];

        return json_encode($errorMessage);
    }
}
