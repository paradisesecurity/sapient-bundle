<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Handler;

use ParadiseSecurity\Bundle\SapientBundle\Factory\PayloadFactoryInterface;
use ParadiseSecurity\Bundle\SapientBundle\Helper\HttpHelperInterface;
use ParadiseSecurity\Bundle\SapientBundle\SapientHeaders;
use ParadiseSecurity\Bundle\SapientBundle\SapientTransitions;
use ParadiseSecurity\Bundle\SapientBundle\StateResolver\StateResolverInterface;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StateHandler implements StateHandlerInterface
{
    public function __construct(
        private PayloadFactoryInterface $payloadFactory,
        private StateResolverInterface $stateResolver,
        private HttpHelperInterface $httpHelper,
    ) {
    }

    public function handle(
        Request|Response|MessageInterface $message,
        string $transition,
    ): Request|Response|MessageInterface {
        $state = $this->getState($message);

        if (is_null($state)) {
            return $message;
        }

        $payload = $this->payloadFactory->createForState($state);

        $this->stateResolver->resolve($payload, $transition);

        $state = $payload->getState();

        return $this->httpHelper->replaceHeaders($message, SapientHeaders::HEADER_STATE, $state);
    }

    public function create(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface {
        return $this->handle($message, SapientTransitions::TRANSITION_CREATE);
    }

    public function unauthorize(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface {
        return $this->handle($message, SapientTransitions::TRANSITION_UNAUTHORIZE);
    }

    public function sign(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface {
        return $this->handle($message, SapientTransitions::TRANSITION_SIGN);
    }

    public function seal(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface {
        return $this->handle($message, SapientTransitions::TRANSITION_SEAL);
    }

    public function unseal(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface {
        return $this->handle($message, SapientTransitions::TRANSITION_UNSEAL);
    }

    public function verify(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface {
        return $this->handle($message, SapientTransitions::TRANSITION_VERIFY);
    }

    public function fail(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface {
        return $this->handle($message, SapientTransitions::TRANSITION_FAIL);
    }

    public function authorize(
        Request|Response|MessageInterface $message
    ): Request|Response|MessageInterface {
        return $this->handle($message, SapientTransitions::TRANSITION_AUTHORIZE);
    }

    public function can(
        Request|Response|MessageInterface $message,
        string $transition
    ): bool {
        $state = $this->getState($message);

        if (is_null($state)) {
            return $message;
        }

        $payload = $this->payloadFactory->createForState($state);

        return $this->stateResolver->resolvable($payload, $transition);
    }

    public function canCreate(Request|Response|MessageInterface $message): bool
    {
        return $this->can($message, SapientTransitions::TRANSITION_CREATE);
    }

    public function canSign(Request|Response|MessageInterface $message): bool
    {
        return $this->can($message, SapientTransitions::TRANSITION_SIGN);
    }

    public function canSeal(Request|Response|MessageInterface $message): bool
    {
        return $this->can($message, SapientTransitions::TRANSITION_SEAL);
    }

    public function canUnseal(Request|Response|MessageInterface $message): bool
    {
        return $this->can($message, SapientTransitions::TRANSITION_UNSEAL);
    }

    public function canVerify(Request|Response|MessageInterface $message): bool
    {
        return $this->can($message, SapientTransitions::TRANSITION_VERIFY);
    }

    private function getState(Request|Response|MessageInterface $message): ?string
    {
        $state = $this->httpHelper->getHeader($message, SapientHeaders::HEADER_STATE);

        if (empty($state)) {
            return null;
        }

        $state = $state[0];

        if (!is_string($state)) {
            return null;
        }

        return $state;
    }
}
