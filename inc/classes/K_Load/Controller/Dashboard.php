<?php

namespace K_Load\Controller;

use K_Load\Template;
use K_Load\User;

class Dashboard {

	public static function index() {
		Template::render('@dashboard/index.twig');
	}

	public static function settings() {
		if (isset($_POST['save']) && isset($_SESSION['steamid'])) {
			$alert = (User::update($_SESSION['steamid'], $_POST) ? 'Your settings have been saved' : 'Failed to update, please try again');
			User::session($_SESSION['steamid']);
		}

		Template::render('@dashboard/settings.twig', (isset($alert) ? ['alert'=>$alert] : []));
	}
}
