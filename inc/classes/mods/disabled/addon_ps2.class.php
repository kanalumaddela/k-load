<?php

use K_Load\Util;

class addon_ps2 {

	private $points = 0;

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
			Util::log('addons', 'Pointshop 2 - Failed to connect: '.$conn->connect_error);
		} else {
			$steamid = $conn->real_escape_string($steamid);

			$sql = "SELECT `points` FROM `ps2_wallet` INNER JOIN `libk_player` ON `ps2_wallet`.`ownerId` = `libk_player`.`id` WHERE `libk_player`.`steam64` = '$steamid'";
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
				$this->points = (int)$result->fetch_object()->points;
			}
		}
	}

	function data() {
		return number_format($this->points);
	}

}
