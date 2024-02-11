<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

class SapientEvent extends Event
{
    public function __construct(
        private FactoryInterface $factory,
        private ItemInterface $menu
    ) {
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    public function getMenu(): ItemInterface
    {
        return $this->menu;
    }
}
