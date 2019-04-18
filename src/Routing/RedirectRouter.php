<?php

namespace Harmony\Bundle\RoutingBundle\Routing;

use Doctrine\Common\Persistence\ManagerRegistry;
use Harmony\Bundle\RoutingBundle\Model\RedirectRoute;
use Symfony\Bundle\FrameworkBundle\Routing\RedirectableUrlMatcher;
use Symfony\Cmf\Component\Routing\RedirectRouteInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use function explode;
use function is_null;
use function str_replace;
use function strstr;
use function substr;

/**
 * Class RedirectRouter
 *
 * @package Harmony\Extension\RouteManager\Routing
 */
class RedirectRouter implements RouterInterface
{

    /** @var RequestContext $context */
    protected $context;

    /** @var ManagerRegistry $registry */
    protected $registry;

    /** @var RouteCollection $routeCollection */
    private $routeCollection = null;

    /**
     * RedirectRouter constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
        $this->context  = new RequestContext();
    }

    /**
     * Sets the request context.
     *
     * @param RequestContext $context
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * Gets the request context.
     *
     * @return RequestContext The context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Gets the RouteCollection instance associated with this Router.
     *
     * @return RouteCollection A RouteCollection instance
     */
    public function getRouteCollection()
    {
        if (is_null($this->routeCollection)) {
            $this->routeCollection = new RouteCollection();
            $this->initRoutes();
        }

        return $this->routeCollection;
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     * Parameters that reference placeholders in the route pattern will substitute them in the
     * path or host. Extra params are added as query string to the URL.
     * When the passed reference type cannot be generated for the route because it requires a different
     * host or scheme than the current one, the method will return a more comprehensive reference
     * that includes the required params. For example, when you call this method with $referenceType = ABSOLUTE_PATH
     * but the route requires the https scheme whereas the current scheme is http, it will instead return an
     * ABSOLUTE_URL with the https scheme and the current host. This makes sure the generated URL matches
     * the route in any case.
     * If there is no route with the given name, the generator must throw the RouteNotFoundException.
     * The special parameter _fragment will be used as the document fragment suffixed to the final URL.
     *
     * @param string $name          The name of the route
     * @param mixed  $parameters    An array of parameters
     * @param int    $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string The generated URL
     * @throws RouteNotFoundException              If the named route doesn't exist
     * @throws MissingMandatoryParametersException When some parameters are missing that are mandatory for the route
     * @throws InvalidParameterException           When a parameter value for a placeholder is not correct because
     *                                             it does not match the requirement
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        throw new RouteNotFoundException('You cannot generate a url from a redirect');
    }

    /**
     * Tries to match a URL path with a set of routes.
     * If the matcher can not find information, it must throw one of the exceptions documented
     * below.
     *
     * @param string $pathinfo The path info to be parsed (raw format, i.e. not urldecoded)
     *
     * @return array An array of parameters
     * @throws NoConfigurationException  If no routing configuration could be found
     * @throws ResourceNotFoundException If the resource could not be found
     * @throws MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    public function match($pathinfo)
    {
        $urlMatcher = new RedirectableUrlMatcher($this->getRouteCollection(), $this->getContext());
        $result     = $urlMatcher->match($pathinfo);

        return $result;
    }

    private function initRoutes(): void
    {
        $redirects = $this->registry->getRepository(RedirectRouteInterface::class)->findAll();
        /** @var RedirectRoute $redirect */
        foreach ($redirects as $redirect) {
            // Check for wildcard routing and adjust as required
            if ($this->isWildcardRedirect($redirect)) {
                $route = $this->createWildcardRoute($redirect);
            } else {
                $route = $this->createRoute($redirect);
            }

            $this->routeCollection->add('_redirect_route_' . $redirect->getId(), $route);
        }
    }

    /**
     * @param RedirectRoute $redirect
     *
     * @return bool
     */
    private function isWildcardRedirect(RedirectRoute $redirect)
    {
        $origin       = $redirect->getRouteTarget() ? $redirect->getRouteTarget()->getPath() : $redirect->getUri();
        $matchSegment = substr($origin, 0, - 1);
        if (substr($origin, - 2) == '/*') {
            return $this->isPathInfoWildcardMatch($matchSegment);
        }

        return false;
    }

    /**
     * @param $matchSegment
     *
     * @return string
     */
    private function isPathInfoWildcardMatch($matchSegment)
    {
        $path = $this->context->getPathInfo();

        return strstr($path, $matchSegment);
    }

    /**
     * @param RedirectRoute $redirect
     *
     * @return Route
     */
    private function createRoute(RedirectRoute $redirect)
    {
        $defaults = [
            RouteObjectInterface::CONTROLLER_NAME => $redirect->getDefault(RouteObjectInterface::CONTROLLER_NAME),
            'permanent'                           => $redirect->isPermanent(),
            'keepRequestMethod'                   => $redirect->isKeepRequestMethod()
        ];
        if ('redirectAction' === explode('::', $redirect->getDefault(RouteObjectInterface::CONTROLLER_NAME))[1]) {
            $defaults['route']           = $redirect->getRouteTarget()->getName();
            $defaults['keepQueryParams'] = $redirect->isKeepQueryParams();
        } else {
            $defaults['path'] = $redirect->getUri();
        }

        return new Route($redirect->getPath(), $defaults);
    }

    /**
     * @param RedirectRoute $redirect
     *
     * @return Route
     */
    private function createWildcardRoute(RedirectRoute $redirect)
    {
        $origin   = $redirect->getPath();
        $target   = $redirect->getRouteTarget() ? $redirect->getRouteTarget()->getPath() : $redirect->getUri();
        $url      = $this->context->getPathInfo();
        $origin   = substr($origin, 0, - 1);
        $target   = substr($target, 0, - 1);
        $pathInfo = str_replace($origin, $target, $url);
        $this->context->setPathInfo($pathInfo);

        return new Route($url, [
            RouteObjectInterface::CONTROLLER_NAME => $redirect->getDefault(RouteObjectInterface::CONTROLLER_NAME),
            'path'                                => $url,
            'permanent'                           => $redirect->isPermanent(),
            'keepRequestMethod'                   => $redirect->isKeepRequestMethod()
        ]);
    }
}