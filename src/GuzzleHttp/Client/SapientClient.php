<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\GuzzleHttp\Client;

use GuzzleHttp\Client;

class SapientClient extends Client
{
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }
}
