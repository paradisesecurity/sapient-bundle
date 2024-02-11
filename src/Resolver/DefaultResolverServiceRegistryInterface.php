<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Resolver;

interface DefaultResolverServiceRegistryInterface
{
    public const HEADER = HeaderResolver::class;

    public const HOST = HostResolver::class;

    public const PATH = PathResolver::class;

    public const DEFAULT_RESOLVERSS = [
        'header' => self::HEADER,
        'host' => self::HOST,
        'path' => self::PATH,
    ];
}
