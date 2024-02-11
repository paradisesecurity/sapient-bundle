<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Manager;

use ParagonIE\Sapient\CryptographyKeys\SealingPublicKey;
use ParagonIE\Sapient\CryptographyKeys\SealingSecretKey;
use ParagonIE\Sapient\CryptographyKeys\SigningPublicKey;
use ParagonIE\Sapient\CryptographyKeys\SigningSecretKey;

interface KeyChainInterface
{
    public function getPublicSealingKey(string $name): SealingPublicKey;

    public function getPrivateSealingKey(string $name): SealingSecretKey;

    public function getPublicSigningKey(string $name): SigningPublicKey;

    public function getPrivateSigningKey(string $name): SigningSecretKey;
}
