<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Cryptography;

use ParagonIE\Sapient\CryptographyKey;

interface KeyInterface
{
    public function getAlias(): string;

    public function getIdentifier(): string;

    public function getHost(): string;

    public function getModifier(): string;

    public function getType(): string;

    public function getClass(): string;

    public function getKey(): CryptographyKey;
}
