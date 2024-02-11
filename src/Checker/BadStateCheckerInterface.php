<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Checker;

use ParadiseSecurity\Bundle\SapientBundle\Sapient\SapientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Http\Message\MessageInterface;

interface BadStateCheckerInterface
{
    public function check(Request|Response|MessageInterface $message, SapientInterface $sapient, array $states = []): bool;
}
