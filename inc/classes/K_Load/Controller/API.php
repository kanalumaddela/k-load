<?php

namespace K_Load\Controller;

use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\Util;
use K_Load\User;
use Steam;

class API {

	public static function player($steamid, $info = null) {
		if (isset($_SESSION['steamid'])) {
			$session_steamid = $_SESSION['steamid'];
			unset($_SESSION['steamid']);
		}

		$data = (ENABLE_CACHE ? Cache::remember('api-player-'.$steamid, 120, function() use ($steamid) { $data = User::get($steamid) + (Steam::User($steamid) ?? []); return $data; }) : User::get($steamid) + (Steam::User($steamid) ?? []) );

		if (isset($session_steamid)) {
			$_SESSION['steamid'] = $session_steamid;
		}

		$data['success'] = count($data) > 0;
		Util::to_top($data, 'success');
		if (isset($data[$info])) {
			$data = $data[$info];
			if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] == 'raw' && (strpos($info, 'avatar') !== false || strpos($info, 'profileurl') !== false)) {
				Util::redirect($data);
			}
		}

		Util::json($data, true, isset($_GET['formatted']));
	}

	public static function group($name) {
		$data = (ENABLE_CACHE ? Cache::remember('api-group-'.$name, 60, function() use ($name) { return Steam::Group($name)->asXML(); }) : Steam::Group($name)->asXML());

		$data = simplexml_load_string($data);
		$data->success = isset($data);

		Util::json($data, true, ($_SERVER['QUERY_STRING'] == 'formatted'));
	}
}
