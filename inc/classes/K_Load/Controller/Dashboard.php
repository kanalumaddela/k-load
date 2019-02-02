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

	public static function settingsMusic()
    {
        if (isset($_POST['save'])) {
            if (!isset($_POST['music'])) {
                $_POST['music'] = [
                    'enable' => 0,
                    'random' => 0,
                    'volumne' => 15,
                ];
            }

            $_POST['music']['enable'] = isset($_POST['music']['enable']) ? (int) $_POST['music']['enable'] : 0;
            $_POST['music']['random'] = isset($_POST['music']['random']) ? (int) $_POST['music']['random'] : 0;
            $_POST['music']['volume'] = isset($_POST['music']['volume']) ? (int) $_POST['music']['volume'] : 15;

            // todo
            dump($_POST);
            die();
            $data['alert'] = 'test message';
        }

        $music_settings = \Database::conn()->select("select `music` from kload_users where steamid = ?", [$_SESSION['steamid']])->execute();
        $music_settings = json_decode($music_settings, true);

        if (is_null($music_settings) || $music_settings === false) {
            $music_settings = [];
        }

        $data = Template::getData();
        $data['user']['music'] = $music_settings;
        $data['music_files'] = glob(APP_ROOT.'/data/music/*.ogg');

        if (count($data['music_files']) > 0) {
            //$data['music_files_html'] = Template::renderReturn()
        }

        Template::render('@dashboard/settings/music.twig', $data, true);
    }

}
