<?php

use K_Load\Controllers\API;
use K_Load\Controllers\Dashboard;
use K_Load\Controllers\Main;
use K_Load\Test;

/** @var \Phroute\Phroute\RouteCollector $router */
$router->filter('themeCheck', '\K_Load\checkThemeQuery');
$router->filter('auth', '\K_Load\displayLoginPageIfGuest');
$router->filter('admin', 'isAdminUser');
$router->filter('super', 'isSuperUser');
$router->filter('csrf', 'checkForCsrf');

$router->any('/', [Main::class, 'index'], ['before' => 'themeCheck']);
$router->any('/dashboard/logout', [Main::class, 'logout']);

$router->get('/api/player/{steamid:i}/{info}?', [API::class, 'player']);
$router->get('/api/players/{steamids}', [API::class, 'players']);
$router->get('/api/groups/{group:c}', [API::class, 'group']);

$router->group(['before' => 'auth'], function ($router) {
    $router->get('/dashboard', [Dashboard::class, 'index']);
    $router->post('/dashboard', [Dashboard::class, 'indexPost']);
    $router->any('/dashboard/settings', [Dashboard::class, 'settingsRedirect']);
    $router->any('/dashboard/my-settings', [Dashboard::class, 'settings']);
    $router->any('/dashboard/users', [Dashboard::class, 'users']);
    $router->any('/dashboard/users/{steamid:i}', [Dashboard::class, 'user']);

//    $router->group(['before' => 'admin'], function ($router) {
//        $router->any('/dashboard/admin', [Admin\General::class, 'index']);
//        $router->any('/dashboard/admin/core', [Admin\General::class, 'core']);
//        $router->any('/dashboard/admin/general', [Admin\General::class, 'general']);
//        $router->post('/dashboard/admin/general/logo', [Admin\General::class, 'logo']);
//        $router->post('/dashboard/admin/general/logo-upload', [Admin\General::class, 'logoUpload']);
//        $router->post('/dashboard/admin/general/logo-delete', [Admin\General::class, 'logoDelete']);
//        $router->any('/dashboard/admin/backgrounds', [Admin\Backgrounds::class, 'index']);
//        $router->any('/dashboard/admin/backgrounds/upload', [Admin\Backgrounds::class, 'backgroundsUpload']);
//        $router->any('/dashboard/admin/messages', [Admin\Messages::class, 'index']);
//        $router->any('/dashboard/admin/rules', [Admin\Rules::class, 'index']);
//        $router->any('/dashboard/admin/staff', [Admin\Staff::class, 'index']);
//
//        $router->any('/dashboard/admin/music', [Admin\Music::class, 'index']);
//        $router->post('/dashboard/admin/music/upload', [Admin\Music::class, 'musicUpload']);
//        $router->post('/dashboard/admin/music/delete', [Admin\Music::class, 'deleteMusic']);
//        $router->post('/dashboard/admin/music/save-order', [Admin\Music::class, 'saveMusicOrder']);
//
//        $router->get('/dashboard/admin/themes', [Admin\Themes::class, 'index']);
//        $router->any('/dashboard/admin/themes/edit/{theme:c}', [Admin\Themes::class, 'edit']);
//        $router->post('/dashboard/admin/themes/retrieve-file', [Admin\Themes::class, 'retrieveFile']);
//    });

//    $router->any('/dashboard/session', [Test::class, 'session']);
});

//$router->any('/test/exception', [Test::class, 'exception']);
//$router->post('/test/mysql', [Test::class, 'mysql']);
//$router->any('/test/steam/{key}?', [Test::class, 'steam']);
//$router->any('/install', [MainOld::class, 'installFix']);
