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

namespace K_Load\Controller;

use Exception;
use K_Load\Util;
use function dump;

class Test
{
    public static $steamid = '76561198152390718';

    public static function steam($key = null)
    {
        $key = isset($_POST['key']) ? $_POST['key'] : $key;
        $status = \K_Load\Test::steam($key);

        Util::json(['success' => $status, 'message' => ($status ? 'Steam API Key works' : 'Please verify your key and try again')], true);
    }

    public static function mysql()
    {
        $status = \K_Load\Test::mysql($_POST['mysql']);
        Util::json(['success' => $status, 'message' => ($status ? 'MySQL connection established' : 'Please check the log in <code>data/logs/mysql</code>')], true);
    }

    public static function exception()
    {
        throw new Exception('exception test route');
    }

    public static function session()
    {
        echo '<pre>';
        var_dump($_SESSION);
        die();
    }
}
