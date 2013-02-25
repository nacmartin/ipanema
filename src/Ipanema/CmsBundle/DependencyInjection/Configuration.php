<?php

namespace Ipanema\CmsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('ipanema_cms')
            ->children()
                ->enumNode('use_sonata_admin')
                    ->values(array(true, false, 'auto'))
                    ->defaultValue('auto')
                ->end()
                ->enumNode('use_menu')
                    ->values(array(true, false, 'auto'))
                    ->defaultValue('auto')
                ->end()
                ->scalarNode('document_class')->defaultValue('Ipanema\CmsBundle\Document\Page')->end()
                ->scalarNode('generic_controller')->defaultValue('symfony_cmf_content.controller:indexAction')->end()
                ->scalarNode('basepath')->defaultValue('/cms/simple')->end()
                ->arrayNode('routing')
                    ->children()
                        ->scalarNode('content_repository_id')->defaultValue('symfony_cmf_routing_extra.content_repository')->end()
                        ->scalarNode('uri_filter_regexp')->defaultValue('')->end()
                        ->arrayNode('controllers_by_alias')
                            ->useAttributeAsKey('alias')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('controllers_by_class')
                            ->useAttributeAsKey('alias')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('templates_by_class')
                            ->useAttributeAsKey('alias')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('multilang')
                    ->children()
                        ->arrayNode('locales')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
