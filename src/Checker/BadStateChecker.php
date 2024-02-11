<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Checker;

use ParadiseSecurity\Bundle\SapientBundle\Helper\HttpHelperInterface;
use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use ParadiseSecurity\Bundle\SapientBundle\SapientHeaders;
use ParadiseSecurity\Bundle\SapientBundle\Sapient\SapientInterface;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_merge;
use function in_array;

class BadStateChecker implements BadStateCheckerInterface
{
    public function __construct(private HttpHelperInterface $httpHelper)
    {
    }

    public function check(
        Request|Response|MessageInterface $message,
        SapientInterface $sapient,
        array $states = []
    ): bool {
        $badStates = [
            PayloadInterface::STATE_FAILED,
            PayloadInterface::STATE_UNAUTHORIZED,
        ];

        $badStates = array_merge($badStates, $states);

        if ($this->checkState($message, $badStates)) {
            return false;
        }

        return $sapient->isEnabled();
    }

    private function checkState(
        Request|Response|MessageInterface $message,
        array $states
    ): bool {
        $headers = $this->httpHelper->getHeader($message, SapientHeaders::HEADER_STATE);

        foreach ($headers as $state) {
            if (in_array($state, $states, true)) {
                return true;
            }
        }

        return false;
    }
}
