<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Provider;

class ClientNameProvider implements ClientNameProviderInterface
{
    public function provide(array $clients, string $identifier): string
    {
        if (isset($clients[$identifier])) {
            return $clients[$identifier]['alias'];
        }

        return '';
    }
}
