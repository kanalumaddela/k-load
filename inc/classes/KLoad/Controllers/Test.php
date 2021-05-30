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

use Exception;
use KLoad\Util;

class Test extends BaseController
{
    public static function steam($key = null)
    {
        $key = isset($_POST['key']) ? $_POST['key'] : $key;
        $status = \KLoad\Test::steam($key);

        Util::json(['success' => $status, 'message' => ($status ? 'Steam API Key works' : 'Please verify your key and try again')], true);
    }

    public static function mysql()
    {
        $status = \KLoad\Test::mysql($_POST['mysql']);
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
        exit();
    }
}
