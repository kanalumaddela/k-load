<?php

namespace K_Load\Controller;

use K_Load\Util;

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
}
