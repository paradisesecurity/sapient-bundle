<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\StateResolver;

use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;

interface StateResolverInterface
{
    public function resolve(PayloadInterface $payload, string $transition): void;

    public function resolvable(PayloadInterface $payload, string $transition): bool;
}
