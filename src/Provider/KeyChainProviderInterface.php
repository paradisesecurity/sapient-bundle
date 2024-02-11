<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Provider;

use ParadiseSecurity\Bundle\SapientBundle\Manager\KeyChainInterface;

interface KeyChainProviderInterface
{
    public function provide(string $modifier): KeyChainInterface;
}
