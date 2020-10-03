<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2020 Maddela
 * @license   MIT
 */

namespace K_Load\Routing;

class RouteCollector
{
    protected $staticRoutes = [];

    protected $dynamicRoutes = [];

    public function addRoute($method, Route $route = null)
    {
        if ($route->isStatic()) {
            $this->staticRoutes[$route->getUri()] = $route->getData();
        }

        return $route;
    }
}
