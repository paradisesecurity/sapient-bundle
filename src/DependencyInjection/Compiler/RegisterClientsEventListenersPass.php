<?php

namespace ParadiseSecurity\Bundle\SapientBundle\DependencyInjection\Compiler;

use ParadiseSecurity\Bundle\GuzzleBundle\MiddlewareTags;
use ParadiseSecurity\Bundle\SapientBundle\EventListener\InitialRequestEventListener;
use ParadiseSecurity\Bundle\SapientBundle\EventListener\SealRequestEventListener;
use ParadiseSecurity\Bundle\SapientBundle\EventListener\SignRequestEventListener;
use ParadiseSecurity\Bundle\SapientBundle\EventListener\UnsealResponseEventListener;
use ParadiseSecurity\Bundle\SapientBundle\EventListener\VerifyResponseEventListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RegisterClientsEventListenersPass implements CompilerPassInterface
{
    use GuzzleClientTrait;

    public function process(ContainerBuilder $container)
    {
        $sapientClients = $container->findTaggedServiceIds('paradise_security.sapient.client');

        foreach ($sapientClients as $sapientClientId => $tags) {
            $tags = $tags[0];
            $this->processEventListeners($sapientClientId, $tags['alias'], $tags['listeners'], $container);
        }
    }

    protected function processEventListeners(
        string $sapientClientId,
        string $sapientClientAlias,
        array $listeners,
        ContainerBuilder $container
    ) {
        if (!$container->hasDefinition($sapientClientId)) {
            return;
        }

        $sapientClient = $container->findDefinition($sapientClientId);

        $arguments = $sapientClient->getArguments();

        $sapientServers = [];

        if (!empty($arguments)) {
            $sapientServers = $arguments[6];
        }

        $guzzleClientAliases = [];

        $guzzleClients = $container->findTaggedServiceIds(MiddlewareTags::CLIENT_TAG);

        foreach ($guzzleClients as $guzzleClientId => $guzzleClientOptions) {
            $guzzleClientOptions = $guzzleClientOptions[0];
            $sapientServerName = $this->findServer($guzzleClientOptions, $sapientServers);
            if (is_null($sapientServerName)) {
                continue;
            }

            $guzzleClientAliases[] = $guzzleClientOptions['alias'];
            $sapientServers[$sapientServerName]['guzzle_alias'] = $guzzleClientOptions['alias'];
        }

        $arguments[6] = $sapientServers;
        $sapientClient->setArguments($arguments);

        // Fires first on request
        $this->addClientInitialRequestEventListener(
            $sapientClientAlias,
            $guzzleClientAliases,
            $container
        );
        // Fires second on request
        $this->addClientSignRequestEventListener(
            $sapientClientAlias,
            $guzzleClientAliases,
            $listeners,
            $container
        );
        // Fires last on request
        $this->addClientSealRequestEventListener(
            $sapientClientAlias,
            $guzzleClientAliases,
            $listeners,
            $container
        );
        // Fires first on response
        $this->addClientUnsealResponseEventListener(
            $sapientClientAlias,
            $guzzleClientAliases,
            $listeners,
            $container
        );
        // Fires last on response
        $this->addClientVerifyResponseEventListener(
            $sapientClientAlias,
            $guzzleClientAliases,
            $listeners,
            $container
        );
    }

    private function addClientListener(
        string $sapientClientAlias,
        array $guzzleClientAliases,
        string $transaction,
        string $subscriberName,
        string $subscriberClass,
        ContainerBuilder $container,
        int $priority = 0,
    ): void {
        $eventName = sprintf('%s_transaction', $transaction);
        $methodName = sprintf('on%sTransaction', ucfirst($transaction));

        $listener = $this->createDefinitionWithArguments(
            $subscriberClass,
            [
                new Reference(sprintf('paradise_security.sapient.client.%s', $sapientClientAlias)),
                new Reference('paradise_security.sapient.checker.bad_state'),
                new Reference('paradise_security.sapient.handler.state'),
                new Reference('paradise_security.sapient.provider.client_name'),
                new Reference('paradise_security.sapient.helper.http'),
                new Reference('logger'),
            ],
        );

        foreach ($guzzleClientAliases as $guzzleClientAlias) {
            $listener->addTag('kernel.event_listener', [
                'event' => sprintf('paradise_security_guzzle.%s.%s', $eventName, $guzzleClientAlias),
                'method' => $methodName,
                'alias' => sprintf('paradise_security.%s_client.event_listener.%s', $sapientClientAlias, $subscriberName),
                'priority' => $priority,
            ]);
        }

        $container->setDefinition(
            sprintf('paradise_security.%s_client.event_listener.%s', $sapientClientAlias, $subscriberName),
            $listener,
        );
    }

    private function addClientInitialRequestEventListener(
        string $sapientClientAlias,
        array $guzzleClientAliases,
        ContainerBuilder $container
    ): void {
        $this->addClientListener(
            $sapientClientAlias,
            $guzzleClientAliases,
            'pre',
            'initial_request',
            InitialRequestEventListener::class,
            $container,
            30,
        );
    }

    private function addClientSignRequestEventListener(
        string $sapientClientAlias,
        array $guzzleClientAliases,
        array $listeners,
        ContainerBuilder $container
    ): void {
        if ($listeners['sign_request'] === false) {
            return;
        }

        $this->addClientListener(
            $sapientClientAlias,
            $guzzleClientAliases,
            'pre',
            'sign_request',
            SignRequestEventListener::class,
            $container,
            20,
        );
    }

    private function addClientSealRequestEventListener(
        string $sapientClientAlias,
        array $guzzleClientAliases,
        array $listeners,
        ContainerBuilder $container
    ): void {
        if ($listeners['seal_request'] === false) {
            return;
        }

        $this->addClientListener(
            $sapientClientAlias,
            $guzzleClientAliases,
            'pre',
            'seal_request',
            SealRequestEventListener::class,
            $container,
            10
        );
    }

    private function addClientUnsealResponseEventListener(
        string $sapientClientAlias,
        array $guzzleClientAliases,
        array $listeners,
        ContainerBuilder $container
    ): void {
        if ($listeners['unseal_response'] === false) {
            return;
        }
        $this->addClientListener(
            $sapientClientAlias,
            $guzzleClientAliases,
            'post',
            'unseal_response',
            UnsealResponseEventListener::class,
            $container,
            20
        );
    }

    private function addClientVerifyResponseEventListener(
        string $sapientClientAlias,
        array $guzzleClientAliases,
        array $listeners,
        ContainerBuilder $container
    ): void {
        if ($listeners['verify_response'] === false) {
            return;
        }
        $this->addClientListener(
            $sapientClientAlias,
            $guzzleClientAliases,
            'post',
            'verify_response',
            VerifyResponseEventListener::class,
            $container,
            10,
        );
    }

    private function createDefinition(string $class): Definition
    {
        return new Definition($class);
    }

    private function createDefinitionWithArguments(string $class, array $arguments): Definition
    {
        $definition = $this->createDefinition($class);
        $definition->setArguments($arguments);

        return $definition;
    }
}
