<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Sapient;

use ParadiseSecurity\Bundle\SapientBundle\Manager\KeyChainInterface;
use ParadiseSecurity\Bundle\SapientBundle\Provider\KeyChainProvider;
use ParadiseSecurity\Bundle\SapientBundle\Provider\KeyChainProviderInterface;
use ParagonIE\Sapient\Adapter\AdapterInterface;
use ParagonIE\Sapient\Sapient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class SapientAPI extends Sapient implements SapientInterface
{
    protected KeyChainProviderInterface $keyChainProvider;

    public function __construct(
        protected string $name,
        protected string $identifier,
        protected bool $enabled,
        protected string $failState,
        protected string $version,
        protected string $host,
        protected array $clients,
        protected array $accessPoints,
        protected array $activeListeners,
        array $keys,
        AdapterInterface $adapter = null,
    ) {
        $this->keyChainProvider = new KeyChainProvider($keys);

        parent::__construct($adapter);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getFailState(): string
    {
        return $this->failState;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getClients(): array
    {
        return $this->clients;
    }

    public function getAccessPoints(): array
    {
        return $this->accessPoints;
    }

    public function isListenerActive(string $listener, string $action): bool
    {
        $key = sprintf('%s_%s', $action, $listener);

        if (!isset($this->activeListeners[$key])) {
            return false;
        }

        return ($this->activeListeners[$key] === true);
    }

    public function getPublicKeyChain(): KeyChainInterface
    {
        return $this->keyChainProvider->provide('public');
    }

    public function unsealAndReturnPsrRequest(RequestInterface $request): RequestInterface
    {
        $keyChain = $this->keyChainProvider->provide('private');

        return $this->unsealRequest(
            $request,
            $keyChain->getPrivateSealingKey($this->name)
        );
    }

    public function verifyAndReturnSignedPsrRequest(RequestInterface $request, string $client): RequestInterface
    {
        $keyChain = $this->keyChainProvider->provide('public');

        return $this->verifySignedRequest(
            $request,
            $keyChain->getPublicSigningKey($client)
        );
    }

    public function verifyAndReturnSignedPsrResponse(ResponseInterface $response, string $client): ResponseInterface
    {
        $keyChain = $this->keyChainProvider->provide('public');

        return $this->verifySignedResponse(
            $response,
            $keyChain->getPublicSigningKey($client)
        );
    }

    public function unsealAndReturnPsrResponse(ResponseInterface $response): ResponseInterface
    {
        $keyChain = $this->keyChainProvider->provide('private');

        return $this->unsealResponse(
            $response,
            $keyChain->getPrivateSealingKey($this->name)
        );
    }

    public function sealAndReturnPsrResponse(ResponseInterface $response, string $client): ResponseInterface
    {
        $keyChain = $this->keyChainProvider->provide('public');

        return $this->sealResponse(
            $response,
            $keyChain->getPublicSealingKey($client)
        );
    }

    public function signAndReturnPsrResponse(ResponseInterface $response): ResponseInterface
    {
        $keyChain = $this->keyChainProvider->provide('private');

        return $this->signResponse(
            $response,
            $keyChain->getPrivateSigningKey($this->name)
        );
    }

    public function signAndReturnPsrRequest(RequestInterface $request): RequestInterface
    {
        $keyChain = $this->keyChainProvider->provide('private');

        return $this->signRequest(
            $request,
            $keyChain->getPrivateSigningKey($this->name)
        );
    }

    public function sealAndReturnPsrRequest(RequestInterface $request, string $client): RequestInterface
    {
        $keyChain = $this->keyChainProvider->provide('public');

        return $this->sealRequest(
            $request,
            $keyChain->getPublicSealingKey($client)
        );
    }
}
