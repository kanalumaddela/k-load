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

use function strtolower;

class GamemodeController extends BaseController
{
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

    protected function parseData(array $data)
    {
        $parsed = [];

        foreach ($data as $gamemode => $gmData) {
            $gamemode = $this->validateGamemode($gamemode);
            $gmData = $this->parseGmData($gmData);

            $parsed[$gamemode] = $gmData;
        }
    }

    protected function validateGamemode($gamemode)
    {
        // DarkRP
        // PropHunt

        $lower = strtolower($gamemode);
        // darkrp
        // prophunt

        if (isset(self::$gamemodes[$lower]) && $lower !== $gamemode) {
            // todo:
        }

        return $lower;
    }
}
