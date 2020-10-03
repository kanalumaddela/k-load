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

use K_Load\Facades\Cache;
use Phroute\Phroute\RouteDataArray;

class Router
{
    /**
     * @var Dispatcher
     */
    protected static $dispatcher;

    public static function init()
    {
        static::$dispatcher = new Dispatcher(static::getRoutes());
    }

    public static function getRoutes(): RouteDataArray
    {
        return Cache::remember('routes', 0, function () {
            $router = new \Phroute\Phroute\RouteCollector();

            require_once __DIR__.'/routes/web.php';

            $router->group(['prefix' => 'api'], function ($router) {
                require_once __DIR__.'/routes/api.php';
            });

            return $router->getData();
        });
    }

    public static function dispatch($route)
    {
        return static::$dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $route);
    }
}