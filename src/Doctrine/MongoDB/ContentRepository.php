<?php

namespace Harmony\Bundle\RoutingBundle\Doctrine\MongoDB;

use Exception;
use Harmony\Bundle\RoutingBundle\Doctrine\DoctrineProvider;
use Symfony\Cmf\Component\Routing\ContentRepositoryInterface;
use function is_object;

/**
 * Class ContentRepository
 *
 * @package Harmony\Bundle\RoutingBundle\Doctrine\MongoDB
 */
class ContentRepository extends DoctrineProvider implements ContentRepositoryInterface
{

    /**
     * Return a content object by it's id or null if there is none.
     * If the returned content implements RouteReferrersReadInterface, it will
     * be used to get the route from it to generate an URL.
     *
     * @param string $id id of the content object
     *
     * @return object A content that matches this id
     */
    public function findById($id)
    {
        return $this->getObjectManager()->find(null, $id);
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
            return $this->getObjectManager()->getUnitOfWork()->getDocumentId($content);
        }
        catch (Exception $e) {
            return null;
        }
    }
}