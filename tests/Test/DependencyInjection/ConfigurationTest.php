<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\Test\DependencyInjection;

use PHPUnit\Framework\TestCase;
use ParadiseSecurity\Bundle\SapientBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    protected Processor $processor;

    protected Configuration $configuration;

    public function setUp(): void
    {
        $this->processor = new Processor();
        $this->configuration = new Configuration('paradise_security_sapient');
    }

    public function testSingleConnectionConfigWithOptions()
    {
        $config = $this->getBaseConfig();
        $processedConfig = $this->processConfig($config);
        $this->assertEquals($this->getProcessedBaseConfig($config), $processedConfig);
    }

    public function testMissingApiEndpointsOption()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('API requires endpoints');
        $config = $this->getBaseConfig();
        unset($config['paradise_security_sapient']['connections']['test_api']['endpoints']);
        $this->processConfig($config);
    }

    public function testMissingSecretSigningKeyOption()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('signing requires a private key');
        $config = $this->getBaseConfig();
        unset($config['paradise_security_sapient']['connections']['test_api']['keys']['sign']['private']);
        $this->processConfig($config);
    }

    public function testMissingSecretSealingKeyOption()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('sealing requires a private key');
        $config = $this->getBaseConfig();
        unset($config['paradise_security_sapient']['connections']['test_api']['keys']['seal']['private']);
        $this->processConfig($config);
    }

    public function testMissingPublicSigningKeyOption()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('missing public signing key');
        $config = $this->getBaseConfig();
        unset($config['paradise_security_sapient']['connections']['test_api']['connectable_whitelist']['test_client']['public_keys']['sign']);
        $this->processConfig($config);
    }

    public function testMissingPublicSealingKeyOption()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('missing public sealing key');
        $config = $this->getBaseConfig();
        unset($config['paradise_security_sapient']['connections']['test_api']['connectable_whitelist']['test_client']['public_keys']['seal']);
        $this->processConfig($config);
    }

    public function testUniqueIdentifierRequiredOption()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('connectable identifiers must be unique');
        $config = $this->getBaseConfig();
        $copy = $config['paradise_security_sapient']['connections']['test_api']['connectable_whitelist']['test_client'];
        $config['paradise_security_sapient']['connections']['test_api']['connectable_whitelist']['duplicant_client'] = $copy;
        $this->processConfig($config);
    }

    public function testMissingConnectableApiEndpointsOption()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('API requires endpoints');
        $config = $this->getBaseConfig();
        $config['paradise_security_sapient']['connections']['test_api']['connectable_whitelist']['test_client']['api_endpoint'] = true;
        $this->processConfig($config);
    }

    private function processConfig(array $config): array
    {
        return $this->processor->processConfiguration($this->configuration, $config);
    }

    private function replaceInConfig(array $config, array $replacement): array
    {
        $client = $config['paradise_security_guzzle']['clients']['test_client'];

        $client = array_replace_recursive(
            $client,
            $replacement
        );

        $config['paradise_security_guzzle']['clients']['test_client'] = $client;

        return $config;
    }

    private function getProcessedBaseConfig(array $config): array
    {
        return array_merge_recursive(
            $config['paradise_security_sapient'],
            [
                'connections' => [
                    'test_api' => [
                        'fail' => 'closed',
                        'version' => '0.0.1',
                        'security' => [
                            'sign' => true,
                            'seal' => true,
                            'unseal' => true,
                            'verify' => true,
                        ],
                        'connectable_whitelist' => [
                            'test_client' => [
                                'api_endpoint' => false,
                                'endpoints' => []
                            ]
                        ]
                    ]
                ],
                'enable_profiler' => true
            ]
        );
    }

    private function getBaseConfig(): array
    {
        return [
            'paradise_security_sapient' => [
                'connections' => [
                    'test_api' => [
                        'enabled' => true,
                        'api_endpoint' => true,
                        'identifier' => 'unique-id-key',
                        'host' => 'api_test_server.com',
                        'keys' => [
                            'sign' => [
                                'public' => 'key',
                                'private' => 'key'
                            ],
                            'seal' => [
                                'public' => 'key',
                                'private' => 'key'
                            ]
                        ],
                        'endpoints' => [
                            'paradise_security_testing_api'
                        ],
                        'connectable_whitelist' => [
                            'test_client' => [
                                'identifier' => 'api_tester',
                                'host' => 'api_test_client.com',
                                'public_keys' => [
                                    'seal' => 'key',
                                    'sign' => 'key'
                                ]
                            ]
                        ]
                    ]
                ],
                'environment' => 'dev',
            ]
        ];
    }
}
