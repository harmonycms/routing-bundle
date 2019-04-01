<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harmony\Bundle\RoutingBundle\Doctrine\Orm;

use Exception;
use Harmony\Bundle\RoutingBundle\Doctrine\DoctrineProvider;
use Symfony\Cmf\Component\Routing\ContentRepositoryInterface;
use function count;
use function explode;
use function get_class;
use function implode;
use function is_object;
use function reset;
use function sprintf;

/**
 * Abstract content repository for ORM.
 * This repository follows the pattern of FQN:id. That is, the full model class
 * name, then a colon, then the id. For example "Acme\Content:12".
 * This will only work with single column ids.
 *
 * @author teito
 */
class ContentRepository extends DoctrineProvider implements ContentRepositoryInterface
{

    /**
     * Return a content object by it's id or null if there is none.
     * If the returned content implements RouteReferrersReadInterface, it will
     * be used to get the route from it to generate an URL.
     *
     * @param string $id The ID contains both model name and id, separated by a colon
     *
     * @return object A content that matches this id
     */
    public function findById($id)
    {
        list($model, $modelId) = $this->getModelAndId($id);

        return $this->getObjectManager()->getRepository($model)->find($modelId);
    }

    /**
     * Return the content identifier for the provided content object for
     * debugging purposes.
     *
     * @param object $content A content instance
     *
     * @return string|null $id id of the content object or null if unable to determine an id
     */
    public function getContentId($content)
    {
        if (!is_object($content)) {
            return null;
        }

        try {
            $class = get_class($content);
            $meta  = $this->getObjectManager()->getClassMetadata($class);
            $ids   = $meta->getIdentifierValues($content);
            if (1 !== count($ids)) {
                throw new Exception(sprintf('Class "%s" must use only one identifier', $class));
            }

            return implode(':', [$class, reset($ids)]);
        }
        catch (Exception $e) {
            return null;
        }
    }

    /**
     * Determine target class and id for this content.
     *
     * @param mixed $identifier as produced by getContentId
     *
     * @return array with model first element, id second
     */
    protected function getModelAndId($identifier)
    {
        return explode(':', $identifier, 2);
    }
}
