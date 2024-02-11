<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Provider;

use ParadiseSecurity\Bundle\SapientBundle\Manager\KeyChain;
use ParadiseSecurity\Bundle\SapientBundle\Manager\KeyChainInterface;
use ParadiseSecurity\Bundle\SapientBundle\Manager\PrivateKeyChain;
use ParadiseSecurity\Bundle\SapientBundle\Manager\PublicKeyChain;

class KeyChainProvider implements KeyChainProviderInterface
{
    private array $keyRing;

    public function __construct(array $keys)
    {
        $keyRing = [];

        if (isset($keys['private'])) {
            $keyRing['private'] = new PrivateKeyChain($keys['private']);
        }

        if (isset($keys['public'])) {
            $keyRing['public'] = new PublicKeyChain($keys['public']);
        }

        $this->keyRing = $keyRing;
    }

    public function provide(string $modifier): KeyChainInterface
    {
        if (isset($this->keyRing[$modifier])) {
            return $this->keyRing[$modifier];
        }

        return new KeyChain([]);
    }
}
