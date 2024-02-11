<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Sapient;

use ParadiseSecurity\Bundle\SapientBundle\Manager\KeyChainInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface SapientInterface
{
    public function getName(): string;

    public function getIdentifier(): string;

    public function isEnabled(): bool;

    public function getFailState(): string;

    public function getVersion(): string;

    public function getHost(): string;

    public function getClients(): array;

    public function getAccessPoints(): array;

    public function isListenerActive(string $listener, string $action): bool;

    public function getPublicKeyChain(): KeyChainInterface;

    public function unsealAndReturnPsrRequest(RequestInterface $request): RequestInterface;

    public function verifyAndReturnSignedPsrRequest(RequestInterface $request, string $client): RequestInterface;

    public function verifyAndReturnSignedPsrResponse(ResponseInterface $response, string $client): ResponseInterface;

    public function unsealAndReturnPsrResponse(ResponseInterface $response): ResponseInterface;

    public function sealAndReturnPsrResponse(ResponseInterface $response, string $client): ResponseInterface;

    public function signAndReturnPsrResponse(ResponseInterface $response): ResponseInterface;

    public function signAndReturnPsrRequest(RequestInterface $request): RequestInterface;

    public function sealAndReturnPsrRequest(RequestInterface $request, string $client): RequestInterface;
}
