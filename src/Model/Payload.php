<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Model;

class Payload implements PayloadInterface, \JsonSerializable
{
    protected string $state = PayloadInterface::STATE_WAITING;

    public function __construct()
    {
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'state' => $this->state
        ];
    }
}
