<?php

namespace K_Load\Controller;

use K_Load\Template;
use K_Load\Util;
use K_Load\User;

class Users {

	public static function all() {
		if (isset($_SESSION['steamid']) && count($_POST) > 0) {
			if ($_POST['type'] == 'perms') {
				$success = User::updatePerms($_POST['player'], $_POST);
				$data['alert'] = $success ? 'User\'s perms have been updated' : 'Failed to update perms';
			} else {
				User::action($_POST['player'], $_POST);
			}
		}

		if (isset($_GET['search'])) {
			$page = (isset($_GET['pg']) ? $_GET['pg'] : 1);
			$data = User::search($_GET['search'], $page);
		} else {
			$data['total'] = User::total();
			$data['pages'] = ceil($data['total']/USERS_PER_PAGE);
			$data['page'] = (isset($_GET['pg']) ? $_GET['pg'] : 1);
			$users = User::all(($data['page'] <= $data['pages']) ? $data['page'] : 1);
			$data['users'] = isset($users['steamid']) ? [$users] : $users;
			if ($data['page'] > $data['pages']) {
				$data['page'] = $data['pages'];
			}
		}
		$data['permissions'] = User::getPerms();

		Template::render('@dashboard/users.twig', $data);
	}

	public static function get($steamid) {
		if (isset($_SESSION['steamid']) && count($_POST) > 0) {
			User::action($_POST['player'], $_POST);
		}

		$data['player'] = User::get($steamid);

		if ($data['player'] !== false && count($data['player']) > 0) {
			$data['player']['settings'] = json_decode($data['player']['settings'], true);
			Template::render('@dashboard/profile.twig', $data);
		} else {
			Util::redirect('/dashboard/users');
		}
	}
}
