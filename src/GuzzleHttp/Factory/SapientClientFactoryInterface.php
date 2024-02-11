<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\GuzzleHttp\Factory;

use GuzzleHttp\ClientInterface;

interface SapientClientFactoryInterface
{
    public function client(string $name): ClientInterface;

    public function owner(): string;
}
