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

use KLoad\Util;

class addon_darkrp_wallet
{
    private $money = 0;

    public function __construct($steamid)
    {
        $mysql = [
            'host'     => 'localhost',
            'port'     => 3306,
            'user'     => 'root',
            'pass'     => '',
            'database' => '',
        ];

        $conn = new mysqli($mysql['host'].':'.$mysql['port'], $mysql['user'], $mysql['pass'], $mysql['database'], $mysql['port']);
        if ($conn->connect_error) {
            Util::log('addons', 'DarkRP - Failed to connect: '.$conn->connect_error);
        } else {
            $steamid = $conn->real_escape_string($steamid);

            $sql = "SELECT `wallet` FROM `darkrp_player` HERE `uid` = '$steamid'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $this->money = (int) $result->fetch_object()->wallet;
            }
        }
    }

    public function data()
    {
        return '$'.number_format($this->money);
    }
}
