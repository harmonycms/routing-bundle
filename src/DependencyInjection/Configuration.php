<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harmony\Bundle\RoutingBundle\DependencyInjection;

use Harmony\Bundle\RoutingBundle\Routing\DynamicRouter;
use Harmony\Bundle\RoutingBundle\Routing\RedirectRouter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function array_filter;
use function count;

/**
 * This class contains the configuration information for the bundle.
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author David Buchmann
 */
class Configuration implements ConfigurationInterface
{

    /**
     * Returns the config tree builder.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('harmony_routing');
        $root        = $treeBuilder->getRootNode();

        $this->addDynamicSection($root);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $root
     */
    private function addDynamicSection(ArrayNodeDefinition $root)
    {
        $root
            ->children()
                ->arrayNode('dynamic')
                    ->fixXmlConfig('controller_by_type', 'controllers_by_type')
                    ->fixXmlConfig('controller_by_class', 'controllers_by_class')
                    ->fixXmlConfig('template_by_class', 'templates_by_class')
                    ->fixXmlConfig('route_filter_by_id', 'route_filters_by_id')
                    ->fixXmlConfig('locale')
                    ->addDefaultsIfNotSet()
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('route_collection_limit')->defaultValue(0)->end()
                        ->scalarNode('generic_controller')->defaultNull()->end()
                        ->scalarNode('default_controller')->defaultNull()->end()
                        ->arrayNode('controllers_by_type')
                            ->useAttributeAsKey('type')
                            ->prototype('scalar')->end()
                        ->end() // controllers_by_type
                        ->arrayNode('controllers_by_class')
                            ->useAttributeAsKey('class')
                            ->prototype('scalar')->end()
                        ->end() // controllers_by_class
                        ->arrayNode('templates_by_class')
                            ->useAttributeAsKey('class')
                            ->prototype('scalar')->end()
                        ->end() // templates_by_class
                        ->scalarNode('uri_filter_regexp')->defaultValue('')->end()
                        ->scalarNode('route_provider_service_id')->end()
                        ->arrayNode('route_filters_by_id')
                            ->canBeUnset()
                            ->defaultValue([])
                            ->useAttributeAsKey('id')
                            ->prototype('scalar')->end()
                        ->end() // route_filters_by_id
                        ->scalarNode('content_repository_service_id')->end()
                        ->arrayNode('locales')
                            ->prototype('scalar')->end()
                        ->end() // locales
                        ->integerNode('limit_candidates')->defaultValue(20)->end()
                        ->booleanNode('match_implicit_locale')->defaultValue(true)->end()
                        ->booleanNode('auto_locale_pattern')->defaultValue(false)->end()
                        ->scalarNode('url_generator')
                            ->defaultValue('harmony_routing.generator')
                            ->info('URL generator service ID')
                        ->end() // url_generator
                    ->end()
                ->end() // dynamic
            ->end()
        ;
    }
}
