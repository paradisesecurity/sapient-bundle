<?php

namespace ParadiseSecurity\Bundle\SapientBundle;

use ParadiseSecurity\Bundle\SapientBundle\DependencyInjection\Compiler\RegisterClientsEventListenersPass;
use ParadiseSecurity\Bundle\SapientBundle\DependencyInjection\Compiler\RegisterGuzzleClientsInFactoryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ParadiseSecuritySapientBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterGuzzleClientsInFactoryPass());
        $container->addCompilerPass(new RegisterClientsEventListenersPass());
    }
}
