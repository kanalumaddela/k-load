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

use Database;
use Steam;
use function preg_match;

class Test
{
    public static $steamid = '76561198152390718';

    public static function steam($key)
    {
        Steam::Key($key);

        $steamid = self::$steamid;
        if (($nomatch = preg_match('/\d/', $steamid)) === 0 || $nomatch === false) {
            $steamid = '76561198152390718';
        }

        $info = Steam::User($steamid);

        return !empty($info['personaname']);
    }

    public static function mysql($config)
    {
        Database::connect([
            'host' => $config['host'],
            'user' => $config['user'],
            'pass' => $config['pass'],
            'db'   => $config['db'],
            'port' => $config['port'],
        ]);

        return Database::ping();
    }
}
