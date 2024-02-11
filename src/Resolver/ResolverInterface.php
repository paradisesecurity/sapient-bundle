<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Resolver;

use Psr\Http\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ResolverInterface
{
    public function resolve(Request|Response|MessageInterface $message, string $resolvable = null): array;
}
