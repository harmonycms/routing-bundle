<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harmony\Bundle\RoutingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class RouteDefaults extends Constraint
{
    public $message = 'Template "%name%" does not exist.';

    public function validatedBy()
    {
        return 'harmony_routing.validator.route_defaults';
    }
}
