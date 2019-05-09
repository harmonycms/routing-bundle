<?php

namespace Harmony\Bundle\RoutingBundle\DependencyInjection\Compiler;

use Harmony\Bundle\RoutingBundle\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class RouteAutowiringPass
 *
 * @package Harmony\Bundle\RoutingBundle\DependencyInjection\Compiler
 */
class RouteAutowiringPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $container->findDefinition('rollerworks_route_autowiring.routing_slot.main')
            ->setClass(RouteCollectionBuilder::class);
    }
}