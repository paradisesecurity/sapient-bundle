<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Factory;

use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;

interface PayloadFactoryInterface
{
    public function createForState(string $state): PayloadInterface;
}
