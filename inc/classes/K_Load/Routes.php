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

use K_Load\Controllers\Admin;
use K_Load\Controllers\API;
use K_Load\Controllers\Dashboard;
use K_Load\Controllers\Main;
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
        self::$router->filter('admin', 'isAdminUser');
        self::$router->filter('super', 'isSuperUser');

        self::$router->any(APP_PATH, [Main::class, 'index']);
        self::$router->any(APP_PATH.'/new', [Main::class, 'index']);
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

            $router->group(['before' => 'admin'], function ($router) {
                $router->any(APP_PATH.'/dashboard/admin', [Admin::class, 'index']);
                $router->any(APP_PATH.'/dashboard/admin/core', [Admin::class, 'core']);
                $router->any(APP_PATH.'/dashboard/admin/general', [Admin::class, 'general']);
                $router->post(APP_PATH.'/dashboard/admin/general/logo', [Admin::class, 'logo']);
                $router->post(APP_PATH.'/dashboard/admin/general/logo-upload', [Admin::class, 'logoUpload']);
                $router->post(APP_PATH.'/dashboard/admin/general/logo-delete', [Admin::class, 'logoDelete']);
                $router->any(APP_PATH.'/dashboard/admin/backgrounds', [Admin::class, 'backgrounds']);
                $router->any(APP_PATH.'/dashboard/admin/backgrounds/upload', [Admin::class, 'backgroundsUpload']);
                $router->any(APP_PATH.'/dashboard/admin/messages', [Admin::class, 'messages']);
                $router->any(APP_PATH.'/dashboard/admin/rules', [Admin::class, 'rules']);
                $router->any(APP_PATH.'/dashboard/admin/staff', [Admin::class, 'staff']);
                $router->any(APP_PATH.'/dashboard/admin/update', [Admin::class, 'update']);

                $router->any(APP_PATH.'/dashboard/admin/music', [Admin\Music::class, 'index']);
                $router->post(APP_PATH.'/dashboard/admin/music/upload', [Admin\Music::class, 'musicUpload']);
                $router->post(APP_PATH.'/dashboard/admin/music/delete', [Admin\Music::class, 'deleteMusic']);
                $router->post(APP_PATH.'/dashboard/admin/music/save-order', [Admin\Music::class, 'saveMusicOrder']);
            });

            $router->any(APP_PATH.'/dashboard/session', [Test::class, 'session']);
        });

        self::$router->any(APP_PATH.'/test/exception', [Test::class, 'exception']);
        self::$router->post(APP_PATH.'/test/mysql', [Test::class, 'mysql']);
        self::$router->any(APP_PATH.'/test/steam/{key}?', [Test::class, 'steam']);
        self::$router->any(APP_PATH.'/install', [Main::class, 'installFix']);
    }
}
