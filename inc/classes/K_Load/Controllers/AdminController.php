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

namespace K_Load\Controllers;

class AdminController extends BaseController
{
    public static $templateFolder = 'admin';

    protected static $gamemodes = [
        'cinema'        => 'Cinema',
        'demo'          => 'Demo Rules (if you want to test rules without applying them to any actual gamemode)',
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
}
