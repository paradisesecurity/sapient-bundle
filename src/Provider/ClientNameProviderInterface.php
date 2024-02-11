<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Provider;

interface ClientNameProviderInterface
{
    public function provide(array $clients, string $identifier): string;
}
