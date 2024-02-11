<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Resolver;

use ParagonIE\Sapient\Exception\HeaderMissingException;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_filter;
use function count;
use function explode;
use function sprintf;
use function trim;

class HeaderResolver implements ResolverInterface
{
    public function resolve(Request|Response|MessageInterface $message, string $resolvable = null): array
    {
        if (!$resolvable) {
            return [];
        }

        try {
            if ($message instanceof Request || $message instanceof Response) {
                return $this->resolveSymfonyHeader($message, $resolvable);
            }

            if ($message instanceof MessageInterface) {
                return $this->resolvePsrHeader($message, $resolvable);
            }
        } catch (HeaderMissingException $ex) {
            return [];
        }
    }

    private function resolveSymfonyHeader(Request|Response $message, string $header): array
    {
        if (!$message->headers->has($header) || null === $message->headers->get($header)) {
            throw new HeaderMissingException(sprintf('%s header is missing.', $header));
        }

        return $this->resolveHeader($message->headers->all($header));
    }

    private function resolvePsrHeader(MessageInterface $message, string $header): array
    {
        if (!$message->hasHeader($header) || 0 === count($message->getHeader($header))) {
            throw new HeaderMissingException(sprintf('%s header is missing.', $header));
        }

        return $this->resolveHeader($message->getHeader($header));
    }

    private function resolveHeader(array $header): array
    {
        if (empty($header)) {
            return $header;
        }

        $headers = [];

        foreach ($header as $key => $value) {
            $headers = array_merge($headers, array_filter(explode(", ", trim($value))));
        }

        return $headers;
    }
}
