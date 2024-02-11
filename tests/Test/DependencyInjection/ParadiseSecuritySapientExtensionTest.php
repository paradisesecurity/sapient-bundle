<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Test\DependencyInjection;

use PHPUnit\Framework\TestCase;
use ParadiseSecurity\Bundle\SapientBundle\DependencyInjection\Compiler\RegisterClientsEventListenersPass;
use ParadiseSecurity\Bundle\SapientBundle\DependencyInjection\Compiler\RegisterGuzzleClientsInFactoryPass;
use ParadiseSecurity\Bundle\SapientBundle\DependencyInjection\Configuration;
use ParadiseSecurity\Bundle\SapientBundle\DependencyInjection\ParadiseSecuritySapientExtension;
use ParadiseSecurity\Bundle\SapientBundle\GuzzleHttp\Factory\SapientClientFactory;
use ParadiseSecurity\Bundle\SapientBundle\GuzzleHttp\Factory\SapientClientFactoryInterface;
use ParadiseSecurity\Bundle\SapientBundle\Sapient\SapientClient;
use ParadiseSecurity\Bundle\SapientBundle\Test\DependencyInjection\Compiler\PublicForTestsCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use ParadiseSecurity\Bundle\SapientBundle\EventListener\ClientEventListener;

class ParadiseSecuritySapientExtensionTest extends TestCase
{
    public function testClientDefaultConfig()
    {
        $container = $this->loadConfigAndCompileContainer('client_default.yaml');

        $clientConfig = $this->getClientConfig();
        $alias = key($clientConfig);
        $config = $clientConfig[$alias];

        // Guzzle Client Factory
        $factory = sprintf('paradise_security_sapient.factory.guzzle_client.%s', $alias);
        $this->assertTrue($container->hasDefinition($factory));
        $clientFactory = $container->get($factory);
        $this->assertInstanceOf(SapientClientFactory::class, $clientFactory);
        $this->assertTrue($container->hasAlias(SapientClientFactoryInterface::class));
        $this->assertEquals($alias, $clientFactory->owner());
        $factories = $container->findTaggedServiceIds('paradise_security_sapient.guzzle_client_factory');
        foreach ($factories as $factoryId => $tags) {
            $tags = $tags[0];
            $this->assertEquals($factoryId, $factory);
            $this->assertEquals($tags['owner'], $alias);
            $this->assertEquals($tags['identifier'], $config['identifier']);
        }

        // Sapient Client
        $sapient = sprintf('paradise_security.sapient.client.%s', $alias);
        $this->assertTrue($container->hasDefinition($sapient));
        $sapientClient = $container->get($sapient);
        $this->assertInstanceOf(SapientClient::class, $sapientClient);
        $this->assertEquals($sapientClient->getName(), $alias);
        $this->assertEquals($sapientClient->getIdentifier(), $config['identifier']);
        $this->assertTrue($sapientClient->isEnabled());
        $this->assertEquals($sapientClient->getFailState(), 'closed');
        $this->assertEquals($sapientClient->getVersion(), '0.0.1');
        $this->assertEquals($sapientClient->getHost(), $config['host']);
        $this->assertEquals($sapientClient->getAccessPoints(), []);
        $serverConfig = $this->getServerConfig();
        $serverAlias = key($serverConfig);
        $clients = $sapientClient->getClients();
        foreach ($clients as $name => $data) {
            $this->assertEquals($serverConfig[$serverAlias]['identifier'], $name);
            $this->assertEquals($serverAlias, $data['alias']);
            $this->assertEquals($serverConfig[$serverAlias]['host'], $data['host']);
        }
    }

    public function testClientSignsRequestsConfig()
    {
        $container = $this->loadConfigAndCompileContainer('client_signs_requests.yaml');

        $clientConfig = $this->getClientConfig();
        $alias = key($clientConfig);
        $config = $clientConfig[$alias];

        // Sapient Client
        $sapient = sprintf('paradise_security.sapient.client.%s', $alias);
        $sapientClient = $container->get($sapient);
        $this->assertTrue($sapientClient->isListenerActive('request', 'sign'));

        // Listeners
        $initialListener = sprintf('paradise_security.%s_client.event_listener.initial_request', $alias);
        $this->assertTrue($container->hasDefinition($initialListener));
        $signListener = sprintf('paradise_security.%s_client.event_listener.sign_request', $alias);
        $this->assertTrue($container->hasDefinition($signListener));
    }

    public function testClientSealsRequestsConfig()
    {
        $container = $this->loadConfigAndCompileContainer('client_seals_requests.yaml');

        $clientConfig = $this->getClientConfig();
        $alias = key($clientConfig);
        $config = $clientConfig[$alias];

        // Sapient Client
        $sapient = sprintf('paradise_security.sapient.client.%s', $alias);
        $sapientClient = $container->get($sapient);
        $this->assertTrue($sapientClient->isListenerActive('request', 'seal'));

        // Listeners
        $initialListener = sprintf('paradise_security.%s_client.event_listener.initial_request', $alias);
        $this->assertTrue($container->hasDefinition($initialListener));
        $sealListener = sprintf('paradise_security.%s_client.event_listener.seal_request', $alias);
        $this->assertTrue($container->hasDefinition($sealListener));
    }

    public function testClientSignsAndSealsRequestsConfig()
    {
        $container = $this->loadConfigAndCompileContainer('client_signs_and_seals_requests.yaml');

        $clientConfig = $this->getClientConfig();
        $alias = key($clientConfig);
        $config = $clientConfig[$alias];

        // Sapient Client
        $sapient = sprintf('paradise_security.sapient.client.%s', $alias);
        $sapientClient = $container->get($sapient);
        $this->assertTrue($sapientClient->isListenerActive('request', 'sign'));
        $this->assertTrue($sapientClient->isListenerActive('request', 'seal'));

        // Listeners
        $initialListener = sprintf('paradise_security.%s_client.event_listener.initial_request', $alias);
        $this->assertTrue($container->hasDefinition($initialListener));
        $signListener = sprintf('paradise_security.%s_client.event_listener.sign_request', $alias);
        $this->assertTrue($container->hasDefinition($signListener));
        $sealListener = sprintf('paradise_security.%s_client.event_listener.seal_request', $alias);
        $this->assertTrue($container->hasDefinition($sealListener));
    }

    public function testClientVerifiesResponsesConfig()
    {
        $container = $this->loadConfigAndCompileContainer('client_verifies_responses.yaml');

        $clientConfig = $this->getClientConfig();
        $alias = key($clientConfig);
        $config = $clientConfig[$alias];

        // Sapient Client
        $sapient = sprintf('paradise_security.sapient.client.%s', $alias);
        $sapientClient = $container->get($sapient);
        $this->assertTrue($sapientClient->isListenerActive('response', 'verify'));

        // Listeners
        $initialListener = sprintf('paradise_security.%s_client.event_listener.initial_request', $alias);
        $this->assertTrue($container->hasDefinition($initialListener));
        $verifyListener = sprintf('paradise_security.%s_client.event_listener.verify_response', $alias);
        $this->assertTrue($container->hasDefinition($verifyListener));
    }

    public function testClientUnsealsResponsesConfig()
    {
        $container = $this->loadConfigAndCompileContainer('client_unseals_responses.yaml');

        $clientConfig = $this->getClientConfig();
        $alias = key($clientConfig);
        $config = $clientConfig[$alias];

        // Sapient Client
        $sapient = sprintf('paradise_security.sapient.client.%s', $alias);
        $sapientClient = $container->get($sapient);
        $this->assertTrue($sapientClient->isListenerActive('response', 'unseal'));

        // Listeners
        $initialListener = sprintf('paradise_security.%s_client.event_listener.initial_request', $alias);
        $this->assertTrue($container->hasDefinition($initialListener));
        $unsealListener = sprintf('paradise_security.%s_client.event_listener.unseal_response', $alias);
        $this->assertTrue($container->hasDefinition($unsealListener));
    }

    public function testClientVerifiesAndUnsealsResponsesConfig()
    {
        $container = $this->loadConfigAndCompileContainer('client_verifies_and_unseals_responses.yaml');

        $clientConfig = $this->getClientConfig();
        $alias = key($clientConfig);
        $config = $clientConfig[$alias];

        // Sapient Client
        $sapient = sprintf('paradise_security.sapient.client.%s', $alias);
        $sapientClient = $container->get($sapient);
        $this->assertTrue($sapientClient->isListenerActive('response', 'verify'));
        $this->assertTrue($sapientClient->isListenerActive('response', 'unseal'));

        // Listeners
        $initialListener = sprintf('paradise_security.%s_client.event_listener.initial_request', $alias);
        $this->assertTrue($container->hasDefinition($initialListener));
        $verifyListener = sprintf('paradise_security.%s_client.event_listener.verify_response', $alias);
        $this->assertTrue($container->hasDefinition($verifyListener));
        $unsealListener = sprintf('paradise_security.%s_client.event_listener.unseal_response', $alias);
        $this->assertTrue($container->hasDefinition($unsealListener));
    }

    private function getClientConfig(): array
    {
        return [
            'api_test_client' => [
                'identifier' => 'unique-client-key',
                'host' => 'https://localhost/',
                'keys' => [
                    'sign' => [
                        'private' => 'giP81DlS_R3JL4-UnSVbn2I5lm9abv8vA7aLuEdOUB4bfOjlm5vaj57Kn6DcZhv0lcTN20iqYV0M69Tk9XqEGQ=='
                    ],
                    'seal' => [
                        'private' => 'NoxnlCvhxl8NRfCgIhuxm95IE1Y9QFUHMuvDkrWrnQ4='
                    ]
                ]
            ]
        ];
    }

    private function getServerConfig(): array
    {
        return [
            'api_testing_endpoint' => [
                'identifier' => 'unique-server-key',
                'host' => 'https://localhost/',
                'endpoints' => [
                    '/api/test'
                ],
                'public_keys' => [
                    'sign' => 'G3zo5Zub2o-eyp-g3GYb9JXEzdtIqmFdDOvU5PV6hBk=',
                    'seal' => 'tquhje8C_hNdd85R-CzVq7n7MOLqc5h11GJv7Vo7fgc='
                ]
            ]
        ];
    }

    private function loadConfigAndCompileContainer(string $filename): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new ParadiseSecuritySapientExtension());

        $config = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Resources', 'config']);
        $loader = new YamlFileLoader($container, new FileLocator($config));
        $loader->load($filename);

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->addCompilerPass(new RegisterClientsEventListenersPass());
        $container->addCompilerPass(new PublicForTestsCompilerPass());

        $container->compile();

        return $container;
    }
}
