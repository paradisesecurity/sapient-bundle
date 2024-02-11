<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\StateResolver;

use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use ParadiseSecurity\Bundle\SapientBundle\SapientTransitions;
use ParadiseSecurity\Bundle\StateMachineBundle\Adapter\StateMachineAdapterInterface;

final class StateResolver implements StateResolverInterface
{
    public function __construct(private StateMachineAdapterInterface $compositeStateMachine)
    {
    }

    public function resolve(PayloadInterface $payload, string $transition): void
    {
        if ($this->resolvable($payload, $transition)) {
            $this->compositeStateMachine->apply($payload, SapientTransitions::GRAPH, $transition);
        }
    }

    public function resolvable(PayloadInterface $payload, string $transition): bool
    {
        return $this->compositeStateMachine->can($payload, SapientTransitions::GRAPH, $transition);
    }
}
