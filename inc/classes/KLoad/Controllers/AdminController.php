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

class AdminController extends BaseController
{
    protected static string $templateFolder = 'controllers/admin';

    protected static array $gamemodes = [
        'cinema' => 'Cinema',
        'demo' => 'Demo Mode',
        'darkrp' => 'DarkRP',
        'deathrun' => 'Deathrun',
        'jailbreak' => 'Jailbreak',
        'melonbomber' => 'Melon Bomber',
        'militaryrp' => 'MilitaryRP',
        'murder' => 'Murder',
        'morbus' => 'Morbus',
        'policerp' => 'PoliceRP',
        'prophunt' => 'Prophunt',
        'sandbox' => 'Sandbox',
        'santosrp'      => 'SantosRP',
        'schoolrp'      => 'SchoolRP',
        'starwarsrp'    => 'SWRP',
        'stopitslender' => 'Stop it Slender',
        'slashers'      => 'Slashers',
        'terrortown'    => 'TTT',
    ];
}
