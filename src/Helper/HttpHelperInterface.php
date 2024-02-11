<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Helper;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface HttpHelperInterface
{
    public function createPsrRequest(Request $request): RequestInterface;

    public function createSymfonyRequest(RequestInterface $request): Request;

    public function createPsrResponse(Response $response): ResponseInterface;

    public function createSymfonyResponse(ResponseInterface $response): Response;

    public function initializeRequest(Request $oldRequest, Request|RequestInterface $newRequest): void;

    public function replaceHeaders(Request|Response|MessageInterface $message, string $key, string|array $headers): Request|Response|MessageInterface;

    public function getHost(Request|Response|MessageInterface $message): string;

    public function getPath(Request|Response|MessageInterface $message): string;

    public function getHeader(Request|Response|MessageInterface $message, string $header): array;
}
