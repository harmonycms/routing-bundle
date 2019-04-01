<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Harmony\Bundle\RoutingBundle\Routing;

use Symfony\Cmf\Component\Routing\DynamicRouter as BaseDynamicRouter;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use function array_key_exists;

/**
 * Symfony framework integration of the CMF routing component DynamicRouter class.
 *
 * @author Filippo de Santis
 * @author David Buchmann
 * @author Lukas Smith
 * @author Nacho Martìn
 */
class DynamicRouter extends BaseDynamicRouter
{

    /**
     * key for the request attribute that contains the route document.
     */
    const ROUTE_KEY = 'routeDocument';

    /**
     * key for the request attribute that contains the content document if this
     * route has one associated.
     */
    const CONTENT_KEY = 'contentDocument';

    /**
     * key for the request attribute that contains the template this document
     * wants to use.
     */
    const CONTENT_TEMPLATE = 'template';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Put content and template name into the request attributes instead of the
     * route defaults.
     * {@inheritdoc}
     * The match should identify  a controller for symfony. This can either be
     * the fully qualified class name or the service name of a controller that
     * is registered as a service. In both cases, the action to call on that
     * controller is appended, separated with two colons.
     */
    public function match($url)
    {
        $defaults = parent::match($url);

        return $this->cleanDefaults($defaults);
    }

    /**
     * Tries to match a request with a set of routes and returns the array of
     * information for that route.
     * If the matcher can not find information, it must throw one of the
     * exceptions documented below.
     *
     * @param Request $request The request to match
     *
     * @return array An array of parameters
     * @throws ResourceNotFoundException If no matching resource could be found
     * @throws MethodNotAllowedException If a matching resource was found but
     *                                   the request method is not allowed
     */
    public function matchRequest(Request $request)
    {
        $defaults = parent::matchRequest($request);

        return $this->cleanDefaults($defaults, $request);
    }

    /**
     * Set the request stack so that we can find the current request.
     *
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Get the current request from the request stack.
     *
     * @return Request
     * @throws ResourceNotFoundException
     */
    public function getRequest()
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest) {
            throw new ResourceNotFoundException('There is no request in the request stack');
        }

        return $currentRequest;
    }

    /**
     * Clean up the match data and move some values into the request attributes.
     *
     * @param array   $defaults The defaults from the match
     * @param Request $request  The request object if available
     *
     * @return array the updated defaults to return for this match
     */
    protected function cleanDefaults($defaults, Request $request = null)
    {
        if (null === $request) {
            $request = $this->getRequest();
        }

        if (array_key_exists(RouteObjectInterface::ROUTE_OBJECT, $defaults)) {
            $request->attributes->set(self::ROUTE_KEY, $defaults[RouteObjectInterface::ROUTE_OBJECT]);
            unset($defaults[RouteObjectInterface::ROUTE_OBJECT]);
        }

        if (array_key_exists(RouteObjectInterface::CONTENT_OBJECT, $defaults)) {
            $request->attributes->set(self::CONTENT_KEY, $defaults[RouteObjectInterface::CONTENT_OBJECT]);
            unset($defaults[RouteObjectInterface::CONTENT_OBJECT]);
        }

        if (array_key_exists(RouteObjectInterface::TEMPLATE_NAME, $defaults)) {
            $request->attributes->set(self::CONTENT_TEMPLATE, $defaults[RouteObjectInterface::TEMPLATE_NAME]);

            // contentTemplate is deprecated as of version 2.0, to be removed in 3.0
            $request->attributes->set('contentTemplate', $defaults[RouteObjectInterface::TEMPLATE_NAME]);

            unset($defaults[RouteObjectInterface::TEMPLATE_NAME]);
        }

        return $defaults;
    }
}
