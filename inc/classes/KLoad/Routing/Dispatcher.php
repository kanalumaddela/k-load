<?php
/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 kanalumaddela
 * @license   MIT
 */

namespace KLoad\Routing;

use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\Route;
use Phroute\Phroute\RouteDataInterface;

class Dispatcher
{
    public $matchedRoute;

    private $staticRouteMap;

    private $variableRouteData;

    private $filters;

    private $handlerResolver;

    /**
     * Create a new route dispatcher.
     *
     * @param RouteDataInterface $data
     */
    public function __construct(RouteDataInterface $data)
    {
        $this->staticRouteMap = $data->getStaticRoutes();

        $this->variableRouteData = $data->getVariableRoutes();

        $this->filters = $data->getFilters();

        $this->handlerResolver = new RouteResolver();
    }

    /**
     * Dispatch a route for the given HTTP Method / URI.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @throws HttpRouteNotFoundException
     * @throws HttpMethodNotAllowedException
     *
     * @return mixed|null
     */
    public function dispatch($httpMethod, $uri)
    {
        [$handler, $filters, $vars] = $this->dispatchRoute($httpMethod, \trim($uri, '/'));

        [$beforeFilter, $afterFilter] = $this->parseFilters($filters);

        if (($response = $this->dispatchFilters($beforeFilter)) !== null) {
            return $response;
        }

        [$resolvedHandler, $vars] = $this->handlerResolver->resolve($handler, $vars);

        $response = \call_user_func_array($resolvedHandler, $vars);

        return $this->dispatchFilters($afterFilter, $response);
    }

    /**
     * Perform the route dispatching. Check static routes first followed by variable routes.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @throws HttpRouteNotFoundException|HttpMethodNotAllowedException
     *
     * @return mixed
     */
    protected function dispatchRoute($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$uri])) {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }

        return $this->dispatchVariableRoute($httpMethod, $uri);
    }

    /**
     * Handle the dispatching of static routes.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @throws HttpMethodNotAllowedException
     *
     * @return mixed
     */
    protected function dispatchStaticRoute($httpMethod, $uri)
    {
        $routes = $this->staticRouteMap[$uri];

        if (!isset($routes[$httpMethod])) {
            $httpMethod = $this->checkFallbacks($routes, $httpMethod);
        }

        return $routes[$httpMethod];
    }

    /**
     * Check fallback routes: HEAD for GET requests followed by the ANY attachment.
     *
     * @param $routes
     * @param $httpMethod
     *
     * @throws HttpMethodNotAllowedException
     *
     * @return mixed|string
     */
    protected function checkFallbacks($routes, $httpMethod)
    {
        $additional = [Route::ANY];

        if ($httpMethod === Route::HEAD) {
            $additional[] = Route::GET;
        }

        foreach ($additional as $method) {
            if (isset($routes[$method])) {
                return $method;
            }
        }

        $this->matchedRoute = $routes;

        throw new HttpMethodNotAllowedException('Allow: '.\implode(', ', \array_keys($routes)));
    }

    /**
     * Handle the dispatching of variable routes.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @throws HttpRouteNotFoundException
     * @throws HttpMethodNotAllowedException
     *
     * @return mixed
     */
    protected function dispatchVariableRoute($httpMethod, $uri)
    {
        foreach ($this->variableRouteData as $data) {
            if (!\preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            $count = \count($matches);

//            while (!isset($data['routeMap'][$count++]));

            $routes = $data['routeMap'][$count - 1];

            if (!isset($routes[$httpMethod])) {
                $httpMethod = $this->checkFallbacks($routes, $httpMethod);
            }

            foreach (\array_values($routes[$httpMethod][2]) as $i => $varName) {
                if (!isset($matches[$i + 1]) || $matches[$i + 1] === '') {
                    unset($routes[$httpMethod][2][$varName]);
                } else {
                    $routes[$httpMethod][2][$varName] = $matches[$i + 1];
                }
            }

            return $routes[$httpMethod];
        }

//        if ($uri === 'install') {
//            return redirect('/');
//        }

        throw new HttpRouteNotFoundException('Route '.$uri.' does not exist');
    }

    /**
     * Normalise the array filters attached to the route and merge with any global filters.
     *
     * @param $filters
     *
     * @return array
     */
    protected function parseFilters($filters)
    {
        $beforeFilter = [];
        $afterFilter = [];

        if (isset($filters[Route::BEFORE])) {
            $beforeFilter = \array_intersect_key($this->filters, \array_flip((array) $filters[Route::BEFORE]));
        }

        if (isset($filters[Route::AFTER])) {
            $afterFilter = \array_intersect_key($this->filters, \array_flip((array) $filters[Route::AFTER]));
        }

        return [$beforeFilter, $afterFilter];
    }

    /**
     * Dispatch a route filter.
     *
     * @param      $filters
     * @param null $response
     *
     * @return mixed|null
     */
    protected function dispatchFilters($filters, $response = null)
    {
        while ($filter = \array_shift($filters)) {
            $handler = $this->handlerResolver->resolve($filter);

            if (($filteredResponse = \call_user_func($handler, $response)) !== null) {
                return $filteredResponse;
            }
        }

        return $response;
    }
}
