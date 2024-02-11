<?php

namespace ParadiseSecurity\Bundle\SapientBundle\Utility;

use function filter_var;
use function ltrim;
use function parse_url;
use function preg_match;
use function trim;

use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;

final class Utility
{
    public static function sanitizePath(string $path): string|false
    {
        $path = 'https://example.com/'.ltrim(trim($path), '/');

        $path = filter_var($path, FILTER_VALIDATE_URL);

        if ($path !== false) {
            $path = parse_url($path, PHP_URL_PATH);
        }

        return $path;
    }

    public static function sanitizeHost(string $host): string|false
    {
        if (!preg_match("~^[\w]+://~i", $host)) {
            $host = 'https://'.$host;
        }

        $host = filter_var($host, FILTER_VALIDATE_URL);

        if ($host !== false) {
            $host = parse_url($host, PHP_URL_HOST);
        }

        return $host;
    }
}
