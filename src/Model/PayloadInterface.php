<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Model;

interface PayloadInterface
{
    public const STATE_WAITING = 'waiting';

    public const STATE_NEW = 'new';

    public const STATE_SIGNED = 'signed';

    public const STATE_SEALED = 'sealed';

    public const STATE_VERIFIED = 'verified';

    public const STATE_UNSEALED = 'unsealed';

    public const STATE_UNAUTHORIZED = 'unauthorized';

    public const STATE_AUTHORIZED = 'authorized';

    public const STATE_FAILED = 'failed';

    public function getState(): string;

    public function setState(string $state): void;
}
