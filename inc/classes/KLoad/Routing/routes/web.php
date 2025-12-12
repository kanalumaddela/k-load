<?php

/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2025 kanalumaddela
 * @license   MIT
 */

use KLoad\Controllers\Admin;
use KLoad\Controllers\API;
use KLoad\Controllers\Dashboard;
use KLoad\Controllers\Main;
use KLoad\Controllers\Test;
use Phroute\Phroute\RouteCollector;

/** @var RouteCollector $router */
$router->filter('auth', '\KLoad\displayLoginPageIfGuest');
$router->filter('admin', '\KLoad\isAdminUser');
$router->filter('super', 'isSuperUser');
$router->filter('csrf', 'checkForCsrf');

$router->any('/', [Main::class, 'index']);
$router->post('/dashboard/logout', [Main::class, 'logout']);

$router->get('/api/user/{steamid:i}/{info}?', [API::class, 'userInfo']);
$router->get('/api/avatar/{steamid:i}', [API::class, 'avatar']);

$router->group(['before' => 'auth'], function ($router) {
    $router->get('/dashboard', [Dashboard::class, 'index']);
    $router->post('/dashboard', [Dashboard::class, 'indexPost']);
    $router->any('/dashboard/settings', [Dashboard::class, 'settingsRedirect']);
    $router->get('/dashboard/my-settings', [Dashboard::class, 'mySettings']);
    $router->post('/dashboard/my-settings', [Dashboard::class, 'mySettingsPost']);
    $router->get('/dashboard/users', [Dashboard::class, 'users']);
    $router->get('/dashboard/users/{steamid:i}', [Dashboard::class, 'userOldRoute']);
    $router->get('/dashboard/user/{id:i}', [Dashboard::class, 'profile']);
    $router->get('/dashboard/user/background/{steamid:i}', [Dashboard::class, 'getUserBackground']);

    $router->group(['before' => 'admin'], function ($router) {
        $router->any('/dashboard/admin', [Admin\Core::class, 'index']);
        $router->any('/dashboard/admin/core', [Admin\Core::class, 'core']);
        $router->post('/dashboard/admin/core/config-update', [Admin\Core::class, 'configUpdate']);
        $router->post('/dashboard/admin/core/theme-update', [Admin\Core::class, 'themeUpdate']);
        $router->get('/dashboard/admin/general', [Admin\General::class, 'general']);
        $router->post('/dashboard/admin/general', [Admin\General::class, 'generalPost']);
        $router->post('/dashboard/admin/general/logo', [Admin\General::class, 'logo']);
        $router->post('/dashboard/admin/general/logo-upload', [Admin\General::class, 'logoUpload']);
        $router->get('/dashboard/admin/backgrounds', [Admin\Backgrounds::class, 'index']);
//        $router->any('/dashboard/admin/backgrounds/upload', [Admin\Backgrounds::class, 'backgroundsUpload']);
        $router->get('/dashboard/admin/messages', [Admin\Messages::class, 'index']);
        $router->post('/dashboard/admin/messages', [Admin\Messages::class, 'indexPost']);
        $router->get('/dashboard/admin/rules', [Admin\Rules::class, 'index']);
        $router->post('/dashboard/admin/rules', [Admin\Rules::class, 'indexPost']);
        $router->get('/dashboard/admin/staff', [Admin\Staff::class, 'index']);
        $router->post('/dashboard/admin/staff', [Admin\Staff::class, 'indexPost']);

        $router->get('/dashboard/admin/media', [Admin\Media::class, 'index']);
        $router->delete('/dashboard/admin/media', [Admin\Media::class, 'delete']);
        $router->post('/dashboard/admin/media/upload', [Admin\Media::class, 'upload']);
//
//        $router->get('/dashboard/admin/music', [Admin\Music::class, 'index']);
//        $router->post('/dashboard/admin/music', [Admin\Music::class, 'indexPost']);
//        $router->post('/dashboard/admin/music/upload', [Admin\Music::class, 'musicUpload']);
//        $router->post('/dashboard/admin/music/delete', [Admin\Music::class, 'deleteMusic']);
//        $router->post('/dashboard/admin/music/save-order', [Admin\Music::class, 'saveMusicOrder']);
//
//        $router->get('/dashboard/admin/themes', [Admin\Themes::class, 'index']);
//        $router->any('/dashboard/admin/themes/edit/{theme:c}', [Admin\Themes::class, 'edit']);
//        $router->post('/dashboard/admin/themes/retrieve-file', [Admin\Themes::class, 'retrieveFile']);
    });

//    $router->any('/dashboard/session', [Test::class, 'session']);
});

$router->any('/test/constants', [Test::class, 'constants']);
//$router->any('/test/exception', [Test::class, 'exception']);
//$router->post('/test/mysql', [Test::class, 'mysql']);
//$router->any('/test/steam/{key}?', [Test::class, 'steam']);
//$router->any('/install', [MainOld::class, 'installFix']);
