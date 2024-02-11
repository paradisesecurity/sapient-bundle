<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Manager;

class PublicKeyChain extends KeyChain
{
    public function __construct(array $keys)
    {
        parent::__construct($keys);
    }
}
