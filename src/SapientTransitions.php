<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle;

interface SapientTransitions
{
    public const GRAPH = 'paradise_security_sapient';

    public const TRANSITION_CREATE = 'create';

    public const TRANSITION_UNAUTHORIZE = 'unauthorize';

    public const TRANSITION_SIGN = 'sign';

    public const TRANSITION_SEAL = 'seal';

    public const TRANSITION_UNSEAL = 'unseal';

    public const TRANSITION_VERIFY = 'verify';

    public const TRANSITION_FAIL = 'fail';

    public const TRANSITION_AUTHORIZE = 'authorize';
}
