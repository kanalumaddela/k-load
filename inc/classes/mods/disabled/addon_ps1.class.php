<?php

use K_Load\Util;

class addon_ps1 {

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
			Util::log('addons', 'Pointshop 1 - Failed to connect: '.$conn->connect_error);
		} else {
			$authserver = bcsub( $steamid, '76561197960265728' ) & 1;
			$authid = ( bcsub( $steamid, '76561197960265728' ) - $authserver ) / 2;
			$steam32 = "STEAM_0:$authserver:$authid";

			$uniqueid = $conn->real_escape_string(sprintf("%u", crc32("gm_".$steam32."_gm")));
			$sql = "SELECT `points` FROM `pointshop_data` WHERE `uniqueid` = '$uniqueid'";
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
