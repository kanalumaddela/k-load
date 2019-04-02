<?php

namespace K_Load;

class Routes
{
    /**
     * @var \Phroute\Phroute\RouteCollector
     */
    private static $router;

    public static function init()
    {
        self::$router = new \Phroute\Phroute\RouteCollector();

        self::$router->any(APP_PATH, ['K_Load\Controller\Main', 'index']);
        self::$router->any(APP_PATH.'/login', ['K_Load\Controller\Main', 'login']);
        self::$router->any(APP_PATH.'/logout', ['K_Load\Controller\Main', 'logout']);
        self::$router->any(APP_PATH.'/dashboard/login', ['K_Load\Controller\Main', 'login']);
        self::$router->any(APP_PATH.'/dashboard/logout', ['K_Load\Controller\Main', 'logout']);

        self::$router->get(APP_PATH.'/api/player/{steamid:i}/{info}?', ['K_Load\Controller\API', 'player']);
        self::$router->get(APP_PATH.'/api/groups/{group:c}', ['K_Load\Controller\API', 'group']);

        self::$router->any(APP_PATH.'/dashboard', ['K_Load\Controller\Dashboard', 'index']);
        self::$router->any(APP_PATH.'/dashboard/settings', ['K_Load\Controller\Dashboard', 'settings']);
        self::$router->any(APP_PATH.'/dashboard/users', ['K_Load\Controller\Users', 'all']);
        self::$router->any(APP_PATH.'/dashboard/users/{steamid:i}', ['K_Load\Controller\Users', 'get']);

        self::$router->any(APP_PATH.'/dashboard/admin', ['K_Load\Controller\Admin', 'index']);
        self::$router->any(APP_PATH.'/dashboard/admin/general', ['K_Load\Controller\Admin', 'general']);
        self::$router->any(APP_PATH.'/dashboard/admin/backgrounds', ['K_Load\Controller\Admin', 'backgrounds']);
        self::$router->any(APP_PATH.'/dashboard/admin/backgrounds/upload', ['K_Load\Controller\Admin', 'backgroundsUpload']);
        self::$router->any(APP_PATH.'/dashboard/admin/messages', ['K_Load\Controller\Admin', 'messages']);
        self::$router->any(APP_PATH.'/dashboard/admin/rules', ['K_Load\Controller\Admin', 'rules']);
        self::$router->any(APP_PATH.'/dashboard/admin/staff', ['K_Load\Controller\Admin', 'staff']);
        self::$router->any(APP_PATH.'/dashboard/admin/update', ['K_Load\Controller\Admin', 'update']);

        self::$router->any(APP_PATH.'/dashboard/admin/music', ['K_Load\Controller\Admin', 'music']);
        self::$router->post(APP_PATH.'/dashboard/admin/music/upload', ['K_Load\Controller\Admin', 'musicUpload']);
        self::$router->post(APP_PATH.'/dashboard/admin/music/delete', ['K_Load\Controller\Admin', 'deleteMusic']);

        self::$router->post(APP_PATH.'/test/mysql', ['K_Load\Controller\Test', 'mysql']);
        self::$router->any(APP_PATH.'/test/steam/{key}?', ['K_Load\Controller\Test', 'steam']);
    }

    public static function get()
    {
        if (!self::$router) {
            self::init();
        }

        return self::$router->getData();
    }
}
