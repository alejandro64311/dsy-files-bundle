<?php

namespace dsarhoya\DSYFilesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('dsarhoya_dsy_files');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->enumNode('storage_type')->values(['s3', 'local'])->defaultValue('s3')->end()
                ->arrayNode('s3')
                    ->children()
                        ->arrayNode('credentials')
                            ->children()
                                ->scalarNode('key')->end()
                                ->scalarNode('secret')->end()
                                ->scalarNode('bucket')->end()
                                ->scalarNode('region')->end()
                            ->end()
                        ->end()
                        ->arrayNode('named_credentials')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('key')->end()
                                    ->scalarNode('secret')->end()
                                    ->scalarNode('bucket')->end()
                                    ->scalarNode('region')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
