<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Resolver;

use ParadiseSecurity\Bundle\SapientBundle\Exception\UndefinedResolverException;
use ParadiseSecurity\Component\ServiceRegistry\Registry\ServiceRegistryInterface;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DelegatingResolver implements DelegatingResolverInterface
{
    public function __construct(private ServiceRegistryInterface $registry)
    {
    }

    public function resolve(Request|Response|MessageInterface $message, string $resolver, string $resolvable = null): array
    {
        if (!$this->registry->has($resolver)) {
            throw new UndefinedResolverException(sprintf("No %s resolver found", $resolver));
        }

        $resolver = $this->registry->get($resolver);

        return $resolver->resolve($message, $resolvable);
    }
}
