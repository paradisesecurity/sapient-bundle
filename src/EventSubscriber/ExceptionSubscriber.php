<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventSubscriber;

use ParadiseSecurity\Bundle\SapientBundle\Exception\NoKeyFoundForRequesterException;
use ParadiseSecurity\Bundle\SapientBundle\Exception\RequesterHeaderMissingException;
use ParadiseSecurity\Bundle\SapientBundle\Exception\SignerHeaderMissingException;
use ParadiseSecurity\Bundle\SapientBundle\Exception\VerifyRequestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'handleException',
        ];
    }

    public function handleException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof NoKeyFoundForRequesterException
            || $exception instanceof RequesterHeaderMissingException
            || $exception instanceof SignerHeaderMissingException
            || $exception instanceof VerifyRequestException
        ) {
            $event->setThrowable(new BadRequestHttpException($exception->getMessage()));
            $event->stopPropagation();
        }
    }
}
