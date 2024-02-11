<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Cryptography;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Sapient\CryptographyKey;

class Key implements KeyInterface
{
    private CryptographyKey $key;

    public function __construct(
        private string $alias,
        private string $identifier,
        private string $host,
        private string $modifier,
        private string $type,
        private string $class,
        string $key,
    ) {
        try {
            $this->key = new $class(Base64UrlSafe::decode($key));
        } catch (\RangeException $ex) {
            $this->key = new CryptographyKey();
        }
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getModifier(): string
    {
        return $this->modifier;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getKey(): CryptographyKey
    {
        return $this->key;
    }
}
