<?php

namespace ParadiseSecurity\Bundle\SapientBundle\DependencyInjection\Compiler;

use ParadiseSecurity\Bundle\GuzzleBundle\MiddlewareTags;
use ParadiseSecurity\Bundle\SapientBundle\Model\PayloadInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use ParadiseSecurity\Bundle\SapientBundle\SapientHeaders;

class RegisterGuzzleClientsInFactoryPass implements CompilerPassInterface
{
    use GuzzleClientTrait;

    public function process(ContainerBuilder $container)
    {
        $factories = $container->findTaggedServiceIds('paradise_security_sapient.guzzle_client_factory');

        foreach ($factories as $factoryId => $tags) {
            $tags = $tags[0];
            $this->processFactory($factoryId, $tags['owner'], $tags['identifier'], $container);
        }
    }

    public function processFactory(
        string $factoryId,
        string $owner,
        string $identifier,
        ContainerBuilder $container
    ) {
        if (!$container->hasDefinition($factoryId)) {
            return;
        }

        $factory = $container->findDefinition($factoryId);

        $arguments = $factory->getArguments();

        $options = [];

        if (!empty($arguments)) {
            $options = array_pop($arguments);
        }

        $clients = $container->findTaggedServiceIds(MiddlewareTags::CLIENT_TAG);

        foreach ($clients as $clientId => $tags) {
            $tags = $tags[0];
            $server = $this->findServer($tags, $options);
            if (is_null($server)) {
                continue;
            }

            $clientDefinition = $container->findDefinition($clientId);

            $clientArguments = $clientDefinition->getArguments();

            $clientOptions = [];

            if (!empty($clientArguments)) {
                $clientOptions = array_shift($clientArguments);
            }

            $headerOptions = [
                'identifier' => $identifier,
                'state' => PayloadInterface::STATE_WAITING
            ];
            $clientOptions = $this->setDefaultHeaderOptions($clientOptions, $headerOptions);

            array_unshift($clientArguments, $clientOptions);
            $clientDefinition->setArguments($clientArguments);

            $options[$server]['client'] = new Reference($clientId);
        }

        array_push($arguments, $options);
        $factory->setArguments($arguments);
    }

    private function setDefaultHeaderOptions(array $options, array $headerOptions): array
    {
        $options['headers'][SapientHeaders::HEADER_CLIENT_IDENTIFIER] = $headerOptions['identifier'];
        $options['headers'][SapientHeaders::HEADER_STATE] = $headerOptions['state'];
        return $options;
    }
}
