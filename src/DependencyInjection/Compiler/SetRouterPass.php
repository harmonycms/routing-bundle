<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harmony\Bundle\RoutingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Changes the Router implementation.
 */
class SetRouterPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        // only replace the default router by overwriting the 'router' alias if config tells us to
        if ($container->hasParameter('harmony_routing.replace_symfony_router') &&
            true === $container->getParameter('harmony_routing.replace_symfony_router')) {
            $container->setAlias('router', 'harmony_routing.router');
            $container->getAlias('router')->setPublic(true);
        }
    }
}
