<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\GuzzleHttp\Factory;

use GuzzleHttp\ClientInterface;

use function array_key_exists;

class SapientClientFactory implements SapientClientFactoryInterface
{
    public function __construct(private string $owner, private array $clients)
    {
    }

    public function client(string $name): ClientInterface
    {
        if (array_key_exists($name, $this->clients)) {
            return $this->clients[$name]['client'];
        }
    }

    public function owner(): string
    {
        return $this->owner;
    }
}
