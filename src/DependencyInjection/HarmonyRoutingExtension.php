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

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
use Harmony\Bundle\RoutingBundle\Form\Type\RouteTypeType;
use Harmony\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use function array_key_exists;
use function array_keys;
use function array_map;
use function class_exists;
use function count;
use function trim;

/**
 * @author Philippo de Santis
 * @author David Buchmann
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class HarmonyRoutingExtension extends Extension
{

    /**
     * Loads a specific configuration.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        if ($this->isConfigEnabled($container, $config['dynamic'])) {
            $this->setupDynamicRouter($config['dynamic'], $container, $loader);
        }

        $this->setupChainRouter($config, $container, $loader);
        $this->setupFormTypes($config, $container, $loader);

        $loader->load('validators.yaml');
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     */
    public function getNamespace()
    {
        return 'http://cmf.symfony.com/schema/dic/routing';
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader
     *
     * @throws \Exception
     */
    private function setupChainRouter(array $config, ContainerBuilder $container, LoaderInterface $loader)
    {
        $loader->load('routing-chain.yaml');

        // add the routers defined in the configuration mapping
        $router = $container->getDefinition('harmony_routing.chain_router');
        $router->addMethodCall('add', [new Reference('router.default'), 100]);
        foreach ($container->findTaggedServiceIds('harmony_routing.router') as $id => $tags) {
            $priority = $tags[0]['priority'];
            $router->addMethodCall('add', [new Reference($id), $priority]);
        }
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader
     *
     * @throws \Exception
     */
    private function setupFormTypes(array $config, ContainerBuilder $container, LoaderInterface $loader)
    {
        $loader->load('form-type.yaml');

        if (array_key_exists('dynamic', $config)) {
            $routeTypeTypeDefinition = $container->getDefinition(RouteTypeType::class);

            foreach (array_keys($config['dynamic']['controllers_by_type']) as $routeType) {
                $routeTypeTypeDefinition->addMethodCall('addRouteType', [$routeType]);
            }
        }
    }

    /**
     * Set up the DynamicRouter - only to be called if enabled is set to true.
     *
     * @param array            $config    the compiled configuration for the dynamic router
     * @param ContainerBuilder $container the container builder
     * @param LoaderInterface  $loader    the configuration loader
     *
     * @throws \Exception
     */
    private function setupDynamicRouter(array $config, ContainerBuilder $container, LoaderInterface $loader)
    {
        $loader->load('routing-dynamic.yaml');

        // strip whitespace (XML support)
        foreach ([
                     'controllers_by_type',
                     'controllers_by_class',
                     'templates_by_class',
                     'route_filters_by_id'
                 ] as $option) {
            $config[$option] = array_map('trim', $config[$option]);
        }

        $defaultController = $config['default_controller'];
        if (null === $defaultController) {
            $defaultController = $config['generic_controller'];
        }
        $container->setParameter('harmony_routing.default_controller', $defaultController);

        $locales = $config['locales'];
        if (0 === count($locales) && $config['auto_locale_pattern']) {
            throw new InvalidConfigurationException('It makes no sense to activate auto_locale_pattern when no locales are configured.');
        }

        $this->configureParameters($container, $config, [
            'generic_controller'     => 'generic_controller',
            'controllers_by_type'    => 'controllers_by_type',
            'controllers_by_class'   => 'controllers_by_class',
            'templates_by_class'     => 'templates_by_class',
            'uri_filter_regexp'      => 'uri_filter_regexp',
            'route_collection_limit' => 'route_collection_limit',
            'limit_candidates'       => 'dynamic.limit_candidates',
            'locales'                => 'dynamic.locales',
            'auto_locale_pattern'    => 'dynamic.auto_locale_pattern',
        ]);

        $hasProvider          = false;
        $hasContentRepository = false;

        $bundles = $container->getParameter('kernel.bundles');
        if (class_exists(DoctrineMongoDBMappingsPass::class) && isset($bundles['DoctrineMongoDBBundle'])) {
            $this->loadMongoDbProvider($loader, $container, $locales, $config['match_implicit_locale']);
            $hasProvider = $hasContentRepository = true;
        } elseif (class_exists(DoctrineOrmMappingsPass::class) && isset($bundles['DoctrineBundle'])) {
            $this->loadOrmProvider($loader, $container, $config['match_implicit_locale']);
            $hasProvider = $hasContentRepository = true;
        }

        if (isset($config['route_provider_service_id'])) {
            $container->setAlias('harmony_routing.route_provider', $config['route_provider_service_id']);
            $container->getAlias('harmony_routing.route_provider')->setPublic(true);
            $hasProvider = true;
        }

        if (!$hasProvider) {
            throw new InvalidConfigurationException('When the dynamic router is enabled, you need to either enable one of the persistence layers or set the harmony_routing.dynamic.route_provider_service_id option');
        }

        if (isset($config['content_repository_service_id'])) {
            $container->setAlias('harmony_routing.content_repository', $config['content_repository_service_id']);
            $hasContentRepository = true;
        }

        // content repository is optional
        if ($hasContentRepository) {
            $generator = $container->getDefinition('harmony_routing.generator');
            $generator->addMethodCall('setContentRepository', [new Reference('harmony_routing.content_repository')]);
            $container->getDefinition('harmony_routing.enhancer.content_repository')
                ->addTag('dynamic_router_route_enhancer', ['priority' => 100]);
        }

        $dynamic = $container->getDefinition(DynamicRouter::class);

        // if any mappings are defined, set the respective route enhancer
        if (count($config['controllers_by_type']) > 0) {
            $container->getDefinition('harmony_routing.enhancer.controllers_by_type')
                ->addTag('dynamic_router_route_enhancer', ['priority' => 60]);
        }

        if (count($config['controllers_by_class']) > 0) {
            $container->getDefinition('harmony_routing.enhancer.controllers_by_class')
                ->addTag('dynamic_router_route_enhancer', ['priority' => 50]);
        }

        if (count($config['templates_by_class']) > 0) {
            $container->getDefinition('harmony_routing.enhancer.templates_by_class')
                ->addTag('dynamic_router_route_enhancer', ['priority' => 40]);

            /*
             * The CoreBundle prepends the controller from ContentBundle if the
             * ContentBundle is present in the project.
             * If you are sure you do not need a generic controller, set the field
             * to false to disable this check explicitly. But you would need
             * something else like the default_controller to set the controller,
             * as no controller will be set here.
             */
            if (null === $config['generic_controller']) {
                throw new InvalidConfigurationException('If you want to configure templates_by_class, you need to configure the generic_controller option.');
            }

            // if the content class defines the template, we also need to make sure we use the generic controller for those routes
            $controllerForTemplates = [];
            foreach ($config['templates_by_class'] as $key => $value) {
                $controllerForTemplates[$key] = $config['generic_controller'];
            }

            $definition = $container->getDefinition('harmony_routing.enhancer.controller_for_templates_by_class');
            $definition->replaceArgument('$generator', $controllerForTemplates);

            $container->getDefinition('harmony_routing.enhancer.controller_for_templates_by_class')
                ->addTag('dynamic_router_route_enhancer', ['priority' => 30]);
        }

        if (null !== $config['generic_controller'] && $defaultController !== $config['generic_controller']) {
            $container->getDefinition('harmony_routing.enhancer.explicit_template')
                ->addTag('dynamic_router_route_enhancer', ['priority' => 10]);
        }

        if (null !== $defaultController) {
            $container->getDefinition('harmony_routing.enhancer.default_controller')
                ->addTag('dynamic_router_route_enhancer', ['priority' => - 100]);
        }

        if (count($config['route_filters_by_id']) > 0) {
            $matcher = $container->getDefinition('harmony_routing.nested_matcher');

            foreach ($config['route_filters_by_id'] as $id => $priority) {
                $matcher->addMethodCall('addRouteFilter', [new Reference($id), $priority]);
            }
        }

        $dynamic->replaceArgument('$generator', new Reference($config['url_generator']));
    }

    /**
     * @param LoaderInterface  $loader
     * @param ContainerBuilder $container
     * @param array            $locales
     * @param                  $matchImplicitLocale
     *
     * @throws \Exception
     */
    private function loadMongoDbProvider(LoaderInterface $loader, ContainerBuilder $container, array $locales,
                                         $matchImplicitLocale)
    {
        $loader->load('provider-mongodb.yaml');

        $container->setParameter('harmony_routing.backend_type_mongodb', true);

        if (0 === count($locales)) {
            $container->removeDefinition('harmony_routing.mongodb_route_locale_listener');
        } elseif (!$matchImplicitLocale) {
            // remove all but the prefixes configuration from the service definition.
            $definition = $container->getDefinition('harmony_routing.mongodb_candidates_prefix');
            $definition->setArguments([$definition->getArgument(0)]);
        }
    }

    /**
     * @param LoaderInterface  $loader
     * @param ContainerBuilder $container
     * @param                  $matchImplicitLocale
     *
     * @throws \Exception
     */
    private function loadOrmProvider(LoaderInterface $loader, ContainerBuilder $container, $matchImplicitLocale)
    {
        $loader->load('provider-orm.yaml');

        $container->setParameter('harmony_routing.backend_type_orm', true);
        $container->setParameter('harmony_routing.backend_type_orm_default', true);

        if (!$matchImplicitLocale) {
            // remove the locales argument from the candidates
            $container->getDefinition('harmony_routing.orm_candidates')->setArguments([]);
        }
    }

    /**
     * @param ContainerBuilder $container          The container builder
     * @param array            $config             The config array
     * @param array            $settingToParameter An array with setting to parameter mappings (key = setting, value =
     *                                             parameter name without alias prefix)
     */
    private function configureParameters(ContainerBuilder $container, array $config, array $settingToParameter)
    {
        foreach ($settingToParameter as $setting => $parameter) {
            $container->setParameter('harmony_routing.' . $parameter, $config[$setting]);
        }
    }
}
