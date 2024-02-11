<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\EventListener;

use ParadiseSecurity\Bundle\GuzzleBundle\Event\PreTransactionEvent;
use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;

final class InitialRequestEventListener extends ClientEventListener
{
    public function onPreTransaction(PreTransactionEvent $event): void
    {
        $request = $event->getTransaction();
        $serviceName = $event->getServiceName();

        if (!$this->isRequestValid($request, $serviceName)) {
            $event->stopPropagation();
            return;
        }
        $alreadyCreated = [PayloadInterface::STATE_NEW];
        if (!$this->badStateChecker->check($request, $this->sapientClient, $alreadyCreated)) {
            return;
        }

        if ($this->stateHandler->canCreate($request)) {
            $request = $this->stateHandler->create($request);
            $event->setTransaction($request);
        }
    }
}
