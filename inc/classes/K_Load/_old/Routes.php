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

namespace K_Load\_old;

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

        self::$router->any('/', [Main::class, 'index']);
        self::$router->any('/new', [Main::class, 'index']);
        self::$router->any('/dashboard/logout', [Main::class, 'logout']);

        self::$router->get('/api/player/{steamid:i}/{info}?', [API::class, 'player']);
        self::$router->get('/api/players/{steamids}', [API::class, 'players']);
        self::$router->get('/api/groups/{group:c}', [API::class, 'group']);

        self::$router->group(['before' => 'auth'], function ($router) {
            /*
             * @var RouteCollector $router
             */
            $router->any('/dashboard', [Dashboard::class, 'index']);
            $router->any('/dashboard/settings', [Dashboard::class, 'settings']);
            $router->any('/dashboard/users', [Dashboard::class, 'users']);
            $router->any('/dashboard/users/{steamid:i}', [Dashboard::class, 'user']);

            $router->group(['before' => 'admin'], function ($router) {
                $router->any('/dashboard/admin', [Admin\General::class, 'index']);
                $router->any('/dashboard/admin/core', [Admin\General::class, 'core']);
                $router->any('/dashboard/admin/general', [Admin\General::class, 'general']);
                $router->post('/dashboard/admin/general/logo', [Admin\General::class, 'logo']);
                $router->post('/dashboard/admin/general/logo-upload', [Admin\General::class, 'logoUpload']);
                $router->post('/dashboard/admin/general/logo-delete', [Admin\General::class, 'logoDelete']);
                $router->any('/dashboard/admin/backgrounds', [Admin\Backgrounds::class, 'index']);
                $router->any('/dashboard/admin/backgrounds/upload', [Admin\Backgrounds::class, 'backgroundsUpload']);
                $router->any('/dashboard/admin/messages', [Admin\Messages::class, 'index']);
                $router->any('/dashboard/admin/rules', [Admin\Rules::class, 'index']);
                $router->any('/dashboard/admin/staff', [Admin\Staff::class, 'index']);

                $router->any('/dashboard/admin/music', [Admin\Music::class, 'index']);
                $router->post('/dashboard/admin/music/upload', [Admin\Music::class, 'musicUpload']);
                $router->post('/dashboard/admin/music/delete', [Admin\Music::class, 'deleteMusic']);
                $router->post('/dashboard/admin/music/save-order', [Admin\Music::class, 'saveMusicOrder']);

                $router->get('/dashboard/admin/themes', [Admin\Themes::class, 'index']);
                $router->any('/dashboard/admin/themes/edit/{theme:c}', [Admin\Themes::class, 'edit']);
                $router->post('/dashboard/admin/themes/retrieve-file', [Admin\Themes::class, 'retrieveFile']);
            });

            $router->any('/dashboard/session', [Test::class, 'session']);
        });

        self::$router->any('/test/exception', [Test::class, 'exception']);
        self::$router->post('/test/mysql', [Test::class, 'mysql']);
        self::$router->any('/test/steam/{key}?', [Test::class, 'steam']);
        self::$router->any('/install', [MainOld::class, 'installFix']);
    }
}
