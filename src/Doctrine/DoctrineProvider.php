<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harmony\Bundle\RoutingBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Abstract class for doctrine based content repository and route provider
 * implementations.
 *
 * @author Uwe Jäger
 * @author David Buchmann <mail@davidbu.ch>
 */
abstract class DoctrineProvider
{

    /**
     * If this is null, the manager registry will return the default manager.
     *
     * @var string|null Name of object manager to use
     */
    protected $managerName;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * Limit to apply when calling getRoutesByNames() with null.
     *
     * @var int|null
     */
    protected $routeCollectionLimit;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Set the object manager name to use for this loader. If not set, the
     * default manager as decided by the manager registry will be used.
     *
     * @param string|null $managerName
     */
    public function setManagerName($managerName)
    {
        $this->managerName = $managerName;
    }

    /**
     * Set the limit to apply when calling getAllRoutes().
     * Setting the limit to null means no limit is applied.
     *
     * @param int|null $routeCollectionLimit
     */
    public function setRouteCollectionLimit($routeCollectionLimit = null)
    {
        $this->routeCollectionLimit = $routeCollectionLimit;
    }

    /**
     * Get the object manager named $managerName from the registry.
     *
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->managerRegistry->getManager($this->managerName);
    }
}
