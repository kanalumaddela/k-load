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

class RouteCollector extends \FastRoute\RouteCollector
{
    public function get($route, $handler)
    {
        return $this->addRoute('GET', $route, $handler);
    }

    public function addRoute($httpMethod, $route, $handler)
    {
        $route = $this->currentGroupPrefix.$route;
        $routeDatas = $this->routeParser->parse($route);
        foreach ((array) $httpMethod as $method) {
            foreach ($routeDatas as $routeData) {
                return $this->dataGenerator->addRoute($method, $routeData, $handler);
            }
        }
    }
}