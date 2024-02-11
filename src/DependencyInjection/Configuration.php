<?php

declare(strict_types=1);

namespace ParadiseSecurity\Bundle\SapientBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('paradise_security_sapient');

        $rootNode = $treeBuilder->getRootNode();

        $this->addDefaultSetup($rootNode);
        $this->addConnectionsSection($rootNode);

        return $treeBuilder;
    }

    private function addDefaultSetup(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('environment')
                    ->cannotBeEmpty()
                    ->defaultValue('prod')
                ->end()
                ->booleanNode('enable_profiler')
                    ->defaultTrue()
                    ->info('Enable the data collector and profiler integration')
                ->end()
            ->end()
        ;
    }

    private function addConnectionsSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('connections')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->validate()
                            ->ifTrue(function ($v) {
                                return ($v['api_endpoint']) && empty($v['endpoints']);
                            })
                            ->thenInvalid('API requires endpoints')
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) {
                                $security = $v['security'];
                                $sealKeys = $v['keys']['seal'];
                                return ($security['seal'] && !isset($sealKeys['private']));
                            })
                            ->thenInvalid('sealing requires a private key')
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) {
                                $security = $v['security'];
                                $signKeys = $v['keys']['sign'];
                                return ($security['sign'] && !isset($signKeys['private']));
                            })
                            ->thenInvalid('signing requires a private key')
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) {
                                $security = $v['security'];
                                foreach ($v['connectable_whitelist'] as $n => $pk) {
                                    $key = $pk['public_keys'];
                                    if ($security['verify'] && !isset($key['sign'])) {
                                        return true;
                                    }
                                }
                                return false;
                            })
                            ->thenInvalid('missing public signing key')
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) {
                                $security = $v['security'];
                                foreach ($v['connectable_whitelist'] as $n => $pk) {
                                    $key = $pk['public_keys'];
                                    if ($security['unseal'] && !isset($key['seal'])) {
                                        return true;
                                    }
                                }
                                return false;
                            })
                            ->thenInvalid('missing public sealing key')
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) {
                                foreach ($v['connectable_whitelist'] as $n => $i) {
                                    $id = $i['identifier'];
                                    $list = $v['connectable_whitelist'];
                                    unset($list[$n]);
                                    foreach ($list as $ln => $li) {
                                        if ($id === $li['identifier']) {
                                            return true;
                                        }
                                    }
                                }
                                return false;
                            })
                            ->thenInvalid('connectable identifiers must be unique')
                        ->end()
                        ->children()
                            ->booleanNode('enabled')->defaultFalse()->end()
                            ->booleanNode('api_endpoint')->defaultFalse()->end()
                            ->scalarNode('identifier')->isRequired()->end()
                            ->enumNode('fail')
                                ->values(['open', 'closed'])
                                ->cannotBeEmpty()
                                ->defaultValue('closed')
                            ->end()
                            ->scalarNode('version')
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !is_string($v) && !is_float($v);
                                    })
                                    ->thenInvalid('version can be: string or float')
                                ->end()
                                ->info('The version of the API')
                                ->cannotBeEmpty()
                                ->defaultValue('0.0.1')
                            ->end()
                            ->scalarNode('host')
                                ->isRequired()
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !is_string($v);
                                    })
                                    ->thenInvalid('host can be: string')
                                ->end()
                            ->end()
                            ->arrayNode('security')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('sign')->defaultTrue()->end()
                                    ->booleanNode('seal')->defaultTrue()->end()
                                    ->booleanNode('unseal')->defaultTrue()->end()
                                    ->booleanNode('verify')->defaultTrue()->end()
                                ->end()
                            ->end()
                            ->arrayNode('keys')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->arrayNode('sign')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('public')->end()
                                            ->scalarNode('private')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('seal')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('public')->end()
                                            ->scalarNode('private')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('endpoints')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('connectable_whitelist')
                                ->useAttributeAsKey('name')
                                ->arrayPrototype()
                                    ->validate()
                                        ->ifTrue(function ($v) {
                                            return ($v['api_endpoint']) && empty($v['endpoints']);
                                        })
                                        ->thenInvalid('API requires endpoints')
                                    ->end()
                                    ->children()
                                        ->booleanNode('api_endpoint')->defaultFalse()->end()
                                        ->arrayNode('endpoints')
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->scalarNode('identifier')->isRequired()->end()
                                        ->scalarNode('host')
                                            ->isRequired()
                                            ->validate()
                                                ->ifTrue(function ($v) {
                                                    return !is_string($v);
                                                })
                                                ->thenInvalid('host can be: string')
                                            ->end()
                                        ->end()
                                        ->arrayNode('public_keys')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('seal')->end()
                                                ->scalarNode('sign')->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
