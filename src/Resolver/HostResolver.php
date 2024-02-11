<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Resolver;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HostResolver implements ResolverInterface
{
    public function resolve(Request|Response|MessageInterface $message, string $resolvable = null): array
    {
        if ($message instanceof Request) {
            return [$this->resolveSymfonyHeader($message)];
        }

        if ($message instanceof RequestInterface) {
            return [$this->resolvePsrHeader($message)];
        }

        return [''];
    }

    private function resolveSymfonyHeader(Request $message): string
    {
        return $message->getHost();
    }

    private function resolvePsrHeader(RequestInterface $message): string
    {
        return $message->getUri()->getHost();
    }
}
