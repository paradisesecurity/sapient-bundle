<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Resolver;

use ParadiseSecurity\Component\ServiceRegistry\Registry\AbstractDefaultServiceRegistry;

class DefaultResolverServiceRegistry extends AbstractDefaultServiceRegistry implements DefaultResolverServiceRegistryInterface
{
    public function __construct()
    {
        parent::__construct(
            DefaultResolverServiceRegistryInterface::DEFAULT_RESOLVERSS,
            ResolverInterface::class
        );
    }
}
