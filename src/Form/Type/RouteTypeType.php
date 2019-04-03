<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harmony\Bundle\RoutingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RouteTypeType
 *
 * @package Harmony\Bundle\RoutingBundle\Form\Type
 */
class RouteTypeType extends AbstractType
{

    protected $routeTypes = [];

    protected $translator;

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];
        foreach ($this->routeTypes as $routeType) {
            $choices['route_type.' . $routeType] = $routeType;
        }

        $resolver->setDefaults([
            'choices'            => $choices,
            'translation_domain' => 'HarmonyRoutingBundle',
        ]);
    }

    /**
     * Register a route type.
     *
     * @param string $type
     */
    public function addRouteType($type)
    {
        $this->routeTypes[$type] = $type;
    }

    /**
     * Returns the name of the parent type.
     *
     * @return string|null The name of the parent type if any, null otherwise
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * Returns the prefix of the template block name for this type.
     * The block prefix defaults to the underscored short class name with
     * the "Type" suffix removed (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'harmony_routing_route_type';
    }
}
