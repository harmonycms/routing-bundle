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

use Harmony\Bundle\RoutingBundle\Validator\Constraints\RouteDefaultsTwigValidator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * To avoid a BC-Break: If templating component exists, we will use the validator using general templating
 * from FrameworkBundle.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@mayflower.de>
 */
class TemplatingValidatorPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('templating')) {
            $validatorDefinition = $container->getDefinition(RouteDefaultsTwigValidator::class);
            $validatorDefinition->replaceArgument('$twig', new Reference('templating'));
        }
    }
}
