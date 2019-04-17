<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harmony\Bundle\RoutingBundle\Model;

use LogicException;
use Symfony\Cmf\Component\Routing\RedirectRouteInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route as SymfonyRoute;

/**
 * Class RedirectRoute
 *
 * @package Harmony\Bundle\RoutingBundle\Model
 */
abstract class RedirectRoute extends Route implements RedirectRouteInterface
{

    /**
     * Absolute uri to redirect to.
     */
    protected $uri;

    /**
     * The name of a target route (for use with standard symfony routes).
     */
    protected $routeName;

    /**
     * Target route document to redirect to different dynamic route.
     */
    protected $routeTarget;

    /**
     * Whether this is a permanent redirect. Defaults to false.
     */
    protected $permanent = false;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * Never call this, it makes no sense. The redirect route will return $this
     * as route content for the redirection controller to have the redirect route
     * object as content.
     *
     * @param $document
     *
     * @throws LogicException
     */
    public function setContent($document)
    {
        throw new LogicException('Do not set a content for the redirect route. It is its own content.');
    }

    /**
     * Get the content document this route entry stands for. If non-null,
     * the ControllerClassMapper uses it to identify a controller and
     * the content is passed to the controller.
     * If there is no specific content for this url (i.e. its an "application"
     * page), may return null.
     *
     * @return object the document or entity this route entry points to
     */
    public function getContent()
    {
        return $this;
    }

    /**
     * Set the route this redirection route points to. This must be a PHPCR-ODM
     * mapped object.
     *
     * @param SymfonyRoute $document the redirection target route
     */
    public function setRouteTarget(SymfonyRoute $document)
    {
        $this->routeTarget = $document;
    }

    /**
     * Get the target route document this route redirects to.
     * If non-null, it is added as route into the parameters, which will lead
     * to have the generate call issued by the RedirectController to have
     * the target route in the parameters.
     *
     * @return RouteObjectInterface|RedirectRouteInterface the route this redirection points to
     */
    public function getRouteTarget()
    {
        return $this->routeTarget;
    }

    /**
     * Set a symfony route name for this redirection.
     *
     * @param string $routeName
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;
    }

    /**
     * Get the name of the target route for working with the symfony standard
     * router.
     *
     * @return string target route name
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * Set whether this redirection should be permanent or not. Default is
     * false.
     *
     * @param bool $permanent if true this is a permanent redirection
     */
    public function setPermanent($permanent)
    {
        $this->permanent = $permanent;
    }

    /**
     * Whether this should be a permanent or temporary redirect.
     *
     * @return bool
     */
    public function isPermanent()
    {
        return $this->permanent;
    }

    /**
     * Set the parameters for building this route. Used with both route name
     * and target route document.
     *
     * @param array $parameters a hashmap of key to value mapping for route
     *                          parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Get the parameters for the target route router::generate().
     * Note that for the DynamicRouter, you return the target route
     * document as field 'route' of the hashmap.
     *
     * @return array Information to build the route
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set the absolute redirection target URI.
     *
     * @param string $uri the absolute URI
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * Get the absolute uri to redirect to external domains.
     * If this is non-empty, the other methods won't be used.
     *
     * @return string target absolute uri
     */
    public function getUri()
    {
        return $this->uri;
    }
}
