<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Factory;

use ParadiseSecurity\Bundle\SapientBundle\Model\Payload;
use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;

class PayloadFactory implements PayloadFactoryInterface
{
    public function createNew(): PayloadInterface
    {
        return new Payload();
    }

    public function createForState(string $state): PayloadInterface
    {
        $payload = $this->createNew();
        $payload->setState($state);

        return $payload;
    }
}
