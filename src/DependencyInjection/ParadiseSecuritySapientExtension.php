<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\DependencyInjection;

use ParadiseSecurity\Bundle\GuzzleBundle\DependencyInjection\CompilerPass\MiddlewarePass;
use ParadiseSecurity\Bundle\SapientBundle\CryptographyKeyTypes;
use ParadiseSecurity\Bundle\SapientBundle\EventSubscriber\DefaultHeadersResponseSubscriber;
use ParadiseSecurity\Bundle\SapientBundle\EventSubscriber\SealResponseSubscriber;
use ParadiseSecurity\Bundle\SapientBundle\EventSubscriber\SignResponseSubscriber;
use ParadiseSecurity\Bundle\SapientBundle\EventSubscriber\UnsealRequestSubscriber;
use ParadiseSecurity\Bundle\SapientBundle\EventSubscriber\VerifyRequestSubscriber;
use ParadiseSecurity\Bundle\SapientBundle\Exception\ConfigurationRequiredException;
use ParadiseSecurity\Bundle\SapientBundle\GuzzleHttp\Factory\SapientClientFactory;
use ParadiseSecurity\Bundle\SapientBundle\GuzzleHttp\Factory\SapientClientFactoryInterface;
use ParadiseSecurity\Bundle\SapientBundle\GuzzleHttp\Middleware\SapientDefaultHeadersMiddleware;
use ParadiseSecurity\Bundle\SapientBundle\Sapient\SapientClient;
use ParadiseSecurity\Bundle\SapientBundle\Sapient\SapientServer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ParadiseSecuritySapientExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Resources', 'config']);
        $xmlLoader = new XmlFileLoader($container, new FileLocator($configPath));
        $yamlLoader = new YamlFileLoader($container, new FileLocator($configPath));

        $configuration = $this->getConfiguration([], $container);
        $config = $this->processConfiguration($configuration, $configs);

        $xmlLoader->load('services.xml');
        $yamlLoader->load('console.yml');

        $env = $this->resolveEnv($config['environment'], $container);
        $profiler = $config['enable_profiler'];

        $clients = $this->seperateConfigs($config['connections'], false);
        $servers = $this->seperateConfigs($config['connections'], true);

        $this->createServicesForServers($servers, $container);
        $this->createServicesForClients($clients, $container);
    }

    private function resolveEnv(string $env, ContainerBuilder $container): string
    {
        if ($this->isEnvironmentVariable($env)) {
            $env = $container->resolveEnvPlaceholders($env, true);
        }

        return $env;
    }

    private function seperateConfigs(
        array $connections,
        bool $server = true
    ): array {
        $filterd = [];
        foreach ($connections as $name => $config) {
            if ($config['api_endpoint'] === $server) {
                $filterd[$name] = $config;
            }
            foreach ($config['connectable_whitelist'] as $alias => $connection) {
                if ($connection['api_endpoint'] === $server) {
                    unset($filterd[$name]['connectable_whitelist'][$alias]);
                }
            }
        }
        return $filterd;
    }

    private function createServicesForServers(array $servers, ContainerBuilder $container): void
    {
        foreach ($servers as $alias => $config) {
            $this->addSapientServerDefinition($alias, $config, $container);
            // Runs first on request
            $this->addServerUnsealRequestSubscriber($alias, $config, $container);
            // Runs second on request
            $this->addServerVerifyRequestSubscriber($alias, $config, $container);
            // Runs first on response
            $this->addServerDefaultHeadersResponseSubscriber($alias, $config, $container);
            // Runs second on response
            $this->addServerSignResponseSubscriber($alias, $config, $container);
            // Runs third on response
            $this->addServerSealResponseSubscriber($alias, $config, $container);
        }
    }

    private function createServicesForClients(array $clients, ContainerBuilder $container): void
    {
        foreach ($clients as $alias => $config) {
            $this->addSapientClientDefinition($alias, $config, $container);
        }
    }

    private function isEnvironmentVariable(mixed $variable): bool
    {
        if (!is_string($variable)) {
            return false;
        }

        if (!str_starts_with($variable, 'env_')) {
            return false;
        }

        return true;
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

    private function resolveVariable(string $variable, ContainerBuilder $container): string
    {
        if ($this->isEnvironmentVariable($variable)) {
            $variable = $container->resolveEnvPlaceholders($variable, true);
        }

        return $variable;
    }

    private function createKey(string $name, string $identifier, string $host, array $key): array
    {
        return [
            'alias' => $name,
            'identifier' => $identifier,
            'host' => $host,
            'modifier' => $key['modifier'],
            'type' => $key['type'],
            'class' => $key['class'],
            'key' => $key['key'],
        ];
    }

    private function resolveKeys(array $keys, ContainerBuilder $container): array
    {
        $cryptographyKeys = array_merge(
            CryptographyKeyTypes::PUBLIC_CRYPTOGRAPHY_KEYS,
            CryptographyKeyTypes::PRIVATE_CRYPTOGRAPHY_KEYS,
            CryptographyKeyTypes::SHARED_CRYPTOGRAPHY_KEYS
        );

        foreach ($cryptographyKeys as $index => $key) {
            if (isset($keys[$key['type']][$key['modifier']])) {
                $cryptographyKeys[$index]['key'] = $this->resolveVariable($keys[$key['type']][$key['modifier']], $container);

                continue;
            }

            unset($cryptographyKeys[$index]);
        }

        return $cryptographyKeys;
    }

    private function getPrimitiveKeyRing(string $alias, string $identifier, string $host, array $keys): array
    {
        $primitiveKeyRing = [];

        foreach ($keys as $index => $key) {
            $primitiveKeyRing[] = $this->createKey($alias, $identifier, $host, $key);
        }

        return $primitiveKeyRing;
    }

    private function getClients(array $clients): array
    {
        $requesters = [];

        foreach ($clients as $name => $client) {
            if (isset($requesters[$client['identifier']])) {
                throw new ConfigurationRequiredException('You cannot asign the same identifier to connectables.');
            }

            $requesters[$client['identifier']] = [
                'alias' => $name,
                'host' => $client['host']
            ];

            if (!empty($client['endpoints'])) {
                $requesters[$client['identifier']]['endpoints'] = $client['endpoints'];
            }
        }

        return $requesters;
    }

    private function processSharedKeys(array $sharedKeys): array
    {
        $keys = [];

        if (isset($sharedKeys[CryptographyKeyTypes::ENCRYPTING])) {
            $keys[CryptographyKeyTypes::ENCRYPTING]['shared'] = $sharedKeys[CryptographyKeyTypes::ENCRYPTING];
        }
        if (isset($sharedKeys[CryptographyKeyTypes::AUTHENTICATING])) {
            $keys[CryptographyKeyTypes::AUTHENTICATING]['shared'] = $sharedKeys[CryptographyKeyTypes::AUTHENTICATING];
        }

        return $keys;
    }

    private function processPublicKeys(array $publicKeys): array
    {
        $keys = [];

        if (isset($publicKeys[CryptographyKeyTypes::SEALING])) {
            $keys[CryptographyKeyTypes::SEALING]['public'] = $publicKeys[CryptographyKeyTypes::SEALING];
        }
        if (isset($publicKeys[CryptographyKeyTypes::SIGNING])) {
            $keys[CryptographyKeyTypes::SIGNING]['public'] = $publicKeys[CryptographyKeyTypes::SIGNING];
        }

        return $keys;
    }

    private function getClientsPublicKeys(array $clients, ContainerBuilder $container): array
    {
        $clientKeyRing = [];

        foreach ($clients as $name => $client) {
            $keys = [];

            if (isset($client['public_keys'])) {
                $publicKeys = $this->processPublicKeys($client['public_keys']);
                $keys = array_merge($this->resolveKeys($publicKeys, $container), $keys);
            }
            if (isset($client['shared_keys'])) {
                $sharedKeys = $this->processSharedKeys($client['shared_keys']);
                $keys = array_merge($this->resolveKeys($sharedKeys, $container), $keys);
            }

            $clientKeyRing = array_merge($clientKeyRing, $this->getPrimitiveKeyRing($name, $client['identifier'], $client['host'], $keys));

            unset($keys);
        }

        return $clientKeyRing;
    }

    private function addSapientAPIDefinition(
        string $alias,
        string $className,
        string $apiType,
        array $config,
        ContainerBuilder $container,
    ): void {
        $keys = $this->resolveKeys($config['keys'], $container);

        $serverKeys = $this->getPrimitiveKeyRing($alias, $config['identifier'], $config['host'], $keys);

        $endpoints = [];
        $listeners = [];

        foreach ($config['connectable_whitelist'] as $name => $client) {
            if ($apiType === 'server' && $client['api_endpoint']) {
                throw new ConfigurationRequiredException('Cannot asign API endpoints to whitelisted connectables on an API endpoint.');
            }

            if ($apiType === 'client' && !$client['api_endpoint']) {
                throw new ConfigurationRequiredException('Cannot asign non API endpoints to whitelisted connectables on a client server.');
            }
        }

        if ($apiType === 'client') {
            $this->addGuzzleClientFactory($alias, $config['identifier'], $config['connectable_whitelist'], $container);
        }

        $clients = $this->getClients($config['connectable_whitelist']);
        $clientKeys = $this->getClientsPublicKeys($config['connectable_whitelist'], $container);

        if (isset($config['endpoints'])) {
            $endpoints = $config['endpoints'];
        }

        /*
         * clients can:
         * sign & seal requests; unseal & verify responses
         * servers can:
         * unseal & verify requests; sign & seal responses
         */
        foreach ($config['security'] as $key => $value) {
            if ($apiType === 'server') {
                if ($key === 'unseal' || $key === 'verify') {
                    $listeners[sprintf('%s_request', $key)] = $value;
                }
                if ($key === 'sign' || $key === 'seal') {
                    $listeners[sprintf('%s_response', $key)] = $value;
                }
            }
            if ($apiType === 'client') {
                if ($key === 'sign' || $key === 'seal') {
                    $listeners[sprintf('%s_request', $key)] = $value;
                }
                if ($key === 'unseal' || $key === 'verify') {
                    $listeners[sprintf('%s_response', $key)] = $value;
                }
            }
        }

        $container
            ->setDefinition(
                sprintf('paradise_security.sapient.%s.%s', $apiType, $alias),
                $this->createDefinitionWithArguments(
                    $className,
                    [
                        $alias,
                        $config['identifier'],
                        $config['enabled'],
                        $config['fail'],
                        $config['version'],
                        $this->resolveVariable($config['host'], $container),
                        $clients,
                        $endpoints,
                        $listeners,
                        [
                            'private' => $serverKeys,
                            'public' => $clientKeys,
                        ]
                    ]
                )
            )
            ->addTag(sprintf('paradise_security.sapient.%s', $apiType), [
                'alias' => $alias,
                'listeners' => $listeners
            ])
        ;
    }

    private function addSapientServerDefinition(
        string $alias,
        array $config,
        ContainerBuilder $container,
    ): void {
        $this->addSapientAPIDefinition(
            $alias,
            SapientServer::class,
            'server',
            $config,
            $container,
        );
    }

    private function addSapientClientDefinition(
        string $alias,
        array $config,
        ContainerBuilder $container,
    ): void {
        $this->addSapientAPIDefinition(
            $alias,
            SapientClient::class,
            'client',
            $config,
            $container,
        );
    }

    private function addServerSubscriber(
        string $alias,
        string $subscriberName,
        string $subscriberClass,
        ContainerBuilder $container
    ): void {
        $container
            ->setDefinition(
                sprintf('paradise_security.%s_server.subscriber.%s', $alias, $subscriberName),
                $this->createDefinitionWithArguments(
                    $subscriberClass,
                    [
                        new Reference(sprintf('paradise_security.sapient.server.%s', $alias)),
                        new Reference('paradise_security.sapient.checker.bad_state'),
                        new Reference('paradise_security.sapient.handler.state'),
                        new Reference('paradise_security.sapient.provider.client_name'),
                        new Reference('paradise_security.sapient.helper.http'),
                        new Reference('logger'),
                    ]
                )
            )
            ->addTag('kernel.event_subscriber')
        ;
    }

    private function addServerUnsealRequestSubscriber(string $alias, array $config, ContainerBuilder $container): void
    {
        if ($config['security']['unseal'] === false) {
            return;
        }

        $this->addServerSubscriber(
            $alias,
            'unseal_request',
            UnsealRequestSubscriber::class,
            $container,
        );
    }

    private function addServerVerifyRequestSubscriber(string $alias, array $config, ContainerBuilder $container): void
    {
        if ($config['security']['verify'] === false) {
            return;
        }

        $this->addServerSubscriber(
            $alias,
            'verify_request',
            VerifyRequestSubscriber::class,
            $container,
        );
    }

    private function addServerDefaultHeadersResponseSubscriber(string $alias, array $config, ContainerBuilder $container): void
    {
        $this->addServerSubscriber(
            $alias,
            'default_headers_response',
            DefaultHeadersResponseSubscriber::class,
            $container,
        );
    }

    private function addServerSignResponseSubscriber(string $alias, array $config, ContainerBuilder $container): void
    {
        if ($config['security']['sign'] === false) {
            return;
        }

        $this->addServerSubscriber(
            $alias,
            'sign_response',
            SignResponseSubscriber::class,
            $container,
        );
    }

    private function addServerSealResponseSubscriber(string $alias, array $config, ContainerBuilder $container): void
    {
        if ($config['security']['seal'] === false) {
            return;
        }

        $this->addServerSubscriber(
            $alias,
            'seal_response',
            SealResponseSubscriber::class,
            $container,
        );
    }

    private function addGuzzleClientFactory(
        string $name,
        string $identifier,
        array $servers,
        ContainerBuilder $container
    ): void {
        $clients = [];

        foreach ($servers as $alias => $options) {
            $clients[$alias]['identifier'] = $options['identifier'];
            $clients[$alias]['host'] = $options['host'];
        }

        $serviceName = sprintf('paradise_security_sapient.factory.guzzle_client.%s', $name);

        $container
            ->setDefinition(
                $serviceName,
                $this->createDefinitionWithArguments(
                    SapientClientFactory::class,
                    [
                        $name,
                        $clients,
                    ]
                )
            )
            ->setPublic(true)
            ->addTag(
                'paradise_security_sapient.guzzle_client_factory',
                [
                    'owner' => $name,
                    'identifier' => $identifier
                ]
            )
        ;
        $container->setAlias(SapientClientFactoryInterface::class, $serviceName);
    }

    private function addGuzzleMiddleware(array $servers, ContainerBuilder $container): void
    {
        $clients = [];

        foreach ($servers as $alias => $options) {
            $clients[$alias]['identifier'] = $options['identifier'];
        }

        $container
        ->setDefinition(
            'paradise_security_sapient.guzzle_middleware.default_headers',
            $this->createDefinitionWithArguments(
                SapientDefaultHeadersMiddleware::class,
                [
                    $options['identifier'],
                ]
            )
        )
        ->addTag(MiddlewarePass::MIDDLEWARE_TAG, [
            'alias' => $alias,
            'priority' => 0,
        ])
        ;
    }
}
