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

namespace KLoad\Controllers;

use const KLoad\APP_ROUTE_URL;

class AdminController extends BaseController
{
    protected static array $gamemodes = [
        'cinema'        => 'Cinema',
        'demo'          => 'Demo Mode',
        'darkrp'        => 'DarkRP',
        'deathrun'      => 'Deathrun',
        'jailbreak'     => 'Jailbreak',
        'melonbomber'   => 'Melon Bomber',
        'militaryrp'    => 'MilitaryRP',
        'murder'        => 'Murder',
        'morbus'        => 'Morbus',
        'policerp'      => 'PoliceRP',
        'prophunt'      => 'Prophunt',
        'sandbox'       => 'Sandbox',
        'santosrp'      => 'SantosRP',
        'schoolrp'      => 'SchoolRP',
        'starwarsrp'    => 'SWRP',
        'stopitslender' => 'Stop it Slender',
        'slashers'      => 'Slashers',
        'terrortown'    => 'TTT',
    ];

    private static array $perms = [
        'general',
        'backgrounds',
        'messages',
        'music',
        'rules',
        'staff',
        'themes',
    ];

    public function boot(): void
    {
        $type = \strtolower(\str_replace(__NAMESPACE__.'\\Admin\\', '', static::class));

        static::$templateFolder = 'controllers/admin/'.(!empty(static::$templateFolder) ? static::$templateFolder : $type);
        static::$title = empty(static::$title) ? $type : static::$title;
        static::$route = empty(static::$route) ? $type : static::$route;

        parent::boot(); // TODO: Change the autogenerated stub
    }

    public static function getRoute(): string
    {
        return APP_ROUTE_URL.'/dashboard/admin/'.static::$route;
    }
}
