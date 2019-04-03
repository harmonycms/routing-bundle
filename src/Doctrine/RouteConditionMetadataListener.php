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

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDBClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use LogicException;
use Symfony\Component\Routing\Route;
use function get_class;
use function property_exists;
use function sprintf;

/**
 * Metadata listener to remove mapping for condition field if the field does not exist.
 * The condition option was only added in Symfony 2.4 and is missing from 2.3.
 * When we drop Symfony 2.3 support, this listener can be dropped.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class RouteConditionMetadataListener implements EventSubscriber
{

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return ['loadClassMetadata'];
    }

    /**
     * Handle the load class metadata event: remove translated attribute from
     * fields and remove the locale mapping if present.
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @throws MappingException
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        if (!property_exists(Route::class, 'condition')) {
            return; // nothing to do
        }

        $meta = $eventArgs->getClassMetadata();
        $refl = $meta->getReflectionClass();
        if (null === $refl || Route::class !== $refl->getName()) {
            return;
        }

        if ($meta instanceof OrmClassMetadata) {
            /* @var $meta OrmClassMetadata */
            $meta->mapField([
                'fieldName'  => 'condition',
                'columnName' => 'condition_expr',
                'type'       => 'string',
                'nullable'   => true,
            ]);
        } elseif ($meta instanceof MongoDBClassMetadata) {
            /* @var $meta MongoDBClassMetadata */
            $meta->mapField([
                'fieldName' => 'condition',
                'type'      => 'string',
                'nullable'  => true,
            ]);
        } else {
            throw new LogicException(sprintf('Class metadata was neither MongoDB nor ORM but %s', get_class($meta)));
        }
    }
}
