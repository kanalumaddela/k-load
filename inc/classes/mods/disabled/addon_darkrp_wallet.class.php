<?php

use K_Load\Util;

class addon_darkrp_wallet {

	private $money = 0;

	function __construct($steamid) {

		$mysql = [
			'host' => 'localhost',
			'port' => 3306,
			'user' => 'root',
			'pass' => '',
			'database' => ''
		];

		$conn = new mysqli($mysql['host'].':'.$mysql['port'], $mysql['user'], $mysql['pass'], $mysql['database'], $mysql['port']);
		if ($conn->connect_error) {
			Util::log('addons', 'DarkRP - Failed to connect: '.$conn->connect_error);
		} else {
			$steamid = $conn->real_escape_string($steamid);

			$sql = "SELECT `wallet` FROM `darkrp_player` HERE `uid` = '$steamid'";
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
				$this->money = (int)$result->fetch_object()->wallet;
			}
		}
	}

	function data() {
		return "$".number_format($this->money);
	}

}
