<?php

namespace ParadiseSecurity\Bundle\SapientBundle\DependencyInjection\Compiler;

use ParadiseSecurity\Bundle\SapientBundle\Utility\Utility;

use function array_key_exists;

trait GuzzleClientTrait
{
    protected function findServer(
        array $guzzleClientOptions,
        array $sapientServers
    ): ?string {
        foreach ($sapientServers as $sapientServerName => $sapientServerOptions) {
            if ($this->checkIdentifier($guzzleClientOptions, $sapientServerName, $sapientServerOptions)) {
                return $sapientServerName;
            }
            if ($this->checkHost($guzzleClientOptions, $sapientServerOptions)) {
                return $sapientServerName;
            }
            return null;
        }
    }

    protected function checkIdentifier(
        array $guzzleClientOptions,
        string $sapientServerName,
        array $sapientServerOptions
    ): bool {
        if (!isset($guzzleClientOptions['identifier'])) {
            return false;
        }

        $identifiers = [$sapientServerName];

        if (isset($sapientServerOptions['identifier'])) {
            $identifiers[] = $sapientServerOptions['identifier'];
        }

        return array_key_exists($guzzleClientOptions['identifier'], $identifiers);
    }

    protected function checkHost(array $guzzleClientOptions, array $sapientServerOptions): bool
    {
        if (!isset($guzzleClientOptions['host'])) {
            return false;
        }

        $guzzleClientHost = Utility::sanitizeHost($guzzleClientOptions['host']);

        $sapientServerHost = null;
        if (isset($sapientServerOptions['host'])) {
            $sapientServerHost = Utility::sanitizeHost($sapientServerOptions['host']);
        }

        return ($guzzleClientHost === $sapientServerHost);
    }
}
