<?php

namespace ParadiseSecurity\Bundle\SapientBundle\GuzzleHttp\Middleware;

use ParadiseSecurity\Bundle\SapientBundle\SapientHeaders;
use Psr\Http\Message\RequestInterface;

class SapientDefaultHeadersMiddleware
{
    public function __construct(private string $identifier)
    {
    }

    /**
     * @param callable $handler
     *
     * @return callable
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use (&$handler) {
            if (!$request->hasHeader(SapientHeaders::HEADER_CLIENT_IDENTIFIER)) {
                $request = $request->withHeader(SapientHeaders::HEADER_CLIENT_IDENTIFIER, $this->identifier);
            }

            return $handler($request, $options);
        };
    }
}
