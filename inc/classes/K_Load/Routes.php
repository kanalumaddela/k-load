<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

namespace K_Load;

use K_Load\Controller\Admin;
use K_Load\Controller\API;
use K_Load\Controller\Main;
use K_Load\Controller\Test;
use K_Load\Controllers\Dashboard;
use Phroute\Phroute\RouteCollector;

class Routes
{
    /**
     * @var \Phroute\Phroute\RouteCollector
     */
    private static $router;

    public static function get()
    {
        if (!self::$router) {
            self::init();
        }

        return self::$router->getData();
    }

    public static function init()
    {
        self::$router = new RouteCollector();

        self::$router->filter('auth', 'displayLoginPageIfGuest');

        self::$router->any(APP_PATH, [Main::class, 'index']);
        self::$router->any(APP_PATH.'/new', [Controllers\Main::class, 'index']);
        self::$router->any(APP_PATH.'/dashboard/logout', [Main::class, 'logout']);

        self::$router->get(APP_PATH.'/api/player/{steamid:i}/{info}?', [API::class, 'player']);
        self::$router->get(APP_PATH.'/api/players/{steamids}', [API::class, 'players']);
        self::$router->get(APP_PATH.'/api/groups/{group:c}', [API::class, 'group']);

        self::$router->group(['before' => 'auth'], function ($router) {
            /**
             * @var RouteCollector $router
             */
            $router->any(APP_PATH.'/dashboard', [Dashboard::class, 'index']);
            $router->any(APP_PATH.'/dashboard/settings', [Dashboard::class, 'settings']);
            $router->any(APP_PATH.'/dashboard/users', [Dashboard::class, 'users']);
            $router->any(APP_PATH.'/dashboard/users/{steamid:i}', [Dashboard::class, 'user']);
            //$router->any(APP_PATH.'/dashboard/users', [Users::class, 'all']);
            //$router->any(APP_PATH.'/dashboard/users/{steamid:i}', [Users::class, 'get']);

            $router->any(APP_PATH.'/dashboard/admin', [Admin::class, 'index']);
            $router->any(APP_PATH.'/dashboard/admin/general', [Admin::class, 'general']);
            $router->any(APP_PATH.'/dashboard/admin/backgrounds', [Admin::class, 'backgrounds']);
            $router->any(APP_PATH.'/dashboard/admin/backgrounds/upload', [Admin::class, 'backgroundsUpload']);
            $router->any(APP_PATH.'/dashboard/admin/messages', [Admin::class, 'messages']);
            $router->any(APP_PATH.'/dashboard/admin/rules', [Admin::class, 'rules']);
            $router->any(APP_PATH.'/dashboard/admin/staff', [Admin::class, 'staff']);
            $router->any(APP_PATH.'/dashboard/admin/update', [Admin::class, 'update']);

            $router->any(APP_PATH.'/dashboard/admin/music', [Admin::class, 'music']);
            $router->post(APP_PATH.'/dashboard/admin/music/upload', [Admin::class, 'musicUpload']);
            $router->post(APP_PATH.'/dashboard/admin/music/delete', [Admin::class, 'deleteMusic']);

            $router->any(APP_PATH.'/dashboard/session', [Test::class, 'session']);
        });

        self::$router->any(APP_PATH.'/test/exception', [Test::class, 'exception']);
        self::$router->post(APP_PATH.'/test/mysql', [Test::class, 'mysql']);
        self::$router->any(APP_PATH.'/test/steam/{key}?', [Test::class, 'steam']);
    }
}
