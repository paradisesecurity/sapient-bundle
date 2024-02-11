<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Handler;

use Psr\Http\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface StateHandlerInterface
{
    public function handle(
        Request|Response|MessageInterface $message,
        string $transition,
    ): Request|Response|MessageInterface;

    public function create(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface;

    public function unauthorize(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface;

    public function sign(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface;

    public function seal(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface;

    public function unseal(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface;

    public function verify(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface;

    public function fail(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface;

    public function authorize(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface;

    public function can(
        Request|Response|MessageInterface $message,
        string $transition
    ): bool;

    public function canCreate(Request|Response|MessageInterface $message): bool;

    public function canSign(Request|Response|MessageInterface $message): bool;

    public function canSeal(Request|Response|MessageInterface $message): bool;

    public function canUnseal(Request|Response|MessageInterface $message): bool;

    public function canVerify(Request|Response|MessageInterface $message): bool;
}
