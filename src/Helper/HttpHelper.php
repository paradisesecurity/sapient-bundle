<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Helper;

use ParadiseSecurity\Bundle\SapientBundle\Resolver\DelegatingResolverInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpHelper implements HttpHelperInterface
{
    public function __construct(
        private HttpMessageFactoryInterface $httpMessageFactory,
        private HttpFoundationFactoryInterface $httpFoundationFactory,
        private DelegatingResolverInterface $resolver,
    ) {
    }

    public function createPsrRequest(Request $request): RequestInterface
    {
        return $this->httpMessageFactory->createRequest($request);
    }

    public function createSymfonyRequest(RequestInterface $request): Request
    {
        return $this->httpFoundationFactory->createRequest($request);
    }

    public function createPsrResponse(Response $response): ResponseInterface
    {
        return $this->httpMessageFactory->createResponse($response);
    }

    public function createSymfonyResponse(ResponseInterface $response): Response
    {
        return $this->httpFoundationFactory->createResponse($response);
    }

    public function initializeRequest(Request $oldRequest, Request|RequestInterface $newRequest): void
    {
        if ($newRequest instanceof RequestInterface) {
            $newRequest = $this->createSymfonyRequest($newRequest);
        }

        $oldRequest->initialize(
            $newRequest->query->all(),
            $newRequest->request->all(),
            $newRequest->attributes->all(),
            $newRequest->cookies->all(),
            $newRequest->files->all(),
            $newRequest->server->all(),
            (string) $newRequest->getContent()
        );

        $headers = $newRequest->headers->all();

        $oldRequest->headers->replace($headers);
    }

    public function replaceHeaders(Request|Response|MessageInterface $message, string $key, string|array $headers): Request|Response|MessageInterface
    {
        if (empty($headers)) {
            return $message;
        }

        if ($message instanceof MessageInterface) {
            return $message->withHeader($key, $headers);
        }

        if ($message instanceof Request || $message instanceof Response) {
            $message->headers->set($key, $headers);
            return $message;
        }

        return $message;
    }

    public function getHost(Request|Response|MessageInterface $message): string
    {
        return $this->resolver->resolve($message, 'host')[0];
    }

    public function getPath(Request|Response|MessageInterface $message): string
    {
        return $this->resolver->resolve($message, 'path')[0];
    }

    public function getHeader(Request|Response|MessageInterface $message, string $header): array
    {
        return $this->resolver->resolve($message, 'header', $header);
    }
}
