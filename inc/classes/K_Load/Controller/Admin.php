<?php

namespace K_Load\Controller;

use Database;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\Setup;
use K_Load\Template;
use K_Load\Test;
use K_Load\Util;
use K_Load\User;

class Admin {

	public static function index() {

		$config = include APP_ROOT.'/data/config.php';

		$data = [
			'version' => Util::version(),
			'updates' => Setup::getUpdates(),
			'theme' => $config['loading_theme'] ?? 'default',
			'themes' => Template::loadingThemes(true),
			'api_keys' => [
				'steam' => $config['apikeys']['steam'],
			],
		];
		$data['updates'] = Setup::getUpdates();

		if (isset($_SESSION['steamid'])) {
			if (User::isSuper($_SESSION['steamid']) && isset($_GET['action'])) {
				switch ($_GET['action']) {
					case 'update_apikeys':
						if (count($_POST) > 0) {
							if (isset($_POST['save_apikeys']) && isset($_POST['apikeys'])) {
								if (count($_POST['apikeys']) > 0) {
									if (isset($_POST['apikeys']['steam'])) {
										if (Test::steam($_POST['apikeys']['steam'])) {
											$config['apikeys'] = $_POST['apikeys'];
											array_multisort($config);
											file_put_contents(APP_ROOT.'/data/config.php', '<?php'."\n".'return '.Util::var_export($config).';'."\n".'?>');
											$data['alert'] = 'API key updated';
											$data['api_keys']['steam'] = $config['apikeys']['steam'];
										} else {
											$data['alert'] = 'API key is invalid, please try again';
										}
									}
								}
							}
						}
						break;
					case 'update_theme':
						if (count($_POST) > 0) {
							if (isset($_POST['save_theme']) && isset($_POST['theme'])) {
								if (Template::isLoadingTheme($_POST['theme'])) {
									$config['loading_theme'] = $_POST['theme'];
									$data['theme'] = $_POST['theme'];
									array_multisort($config);
									file_put_contents(APP_ROOT.'/data/config.php', '<?php'."\n".'return '.Util::var_export($config).';'."\n".'?>');
									$data['alert'] = 'Theme updated';
								} else {
									$data['alert'] = 'Not a valid theme, please make sure there is a <code>pages/loading.twig</code> in the theme';
								}
							}
						}
						break;
					case 'update':
						if ($data['updates']['amount'] > 0) {
							Setup::update();
							$data['version'] = Util::version();
							$data['updates'] = Setup::getUpdates();

							$themes = Template::loadingThemes(true);
							$config['loading_themes'] = array_column($themes, 'name');
							file_put_contents(APP_ROOT.'/data/config.php', '<?php'."\n".'return '.Util::var_export($config).';'."\n".'?>');

							$data['alert'] = 'An update was attempted, please make sure everything is working and check the logs';
						} else {
							$data['alert'] = 'There are no updates';
						}
						break;
					case 'refresh_themes':
						$themes = Template::loadingThemes(true);
						$config['loading_themes'] = array_column($themes, 'name');
						file_put_contents(APP_ROOT.'/data/config.php', '<?php'."\n".'return '.Util::var_export($config).';'."\n".'?>');
						$data['alert'] = 'Themes have been refreshed and added to config';
						break;
					case 'clear_cache':
						if (file_exists(APP_ROOT.'/data/cache')) {
							Util::rmDir(APP_ROOT.'/data/cache');
							Util::log('action', 'Attempted to clear all cache');
						}
						$data['alert'] = !file_exists(APP_ROOT.'/data/cache') ? 'All cache has been deleted' : 'Failed to clear all cache';
						break;
					case 'clear_cache_data':
						Util::log('action', 'Attempted to clear cached data');
						$data['alert'] = Cache::clear() ? 'Cached data has been cleared' : 'Failed to delete cached data';
						break;
					case 'clear_cache_template':
						if (file_exists(APP_ROOT.'/data/cache/templates')) {
							Util::rmDir(APP_ROOT.'/data/cache/templates');
							Util::log('action', 'Attempted to clear template cache');
						}
						$data['alert'] = !file_exists(APP_ROOT.'/data/cache/templates') ? 'Template cache has been deleted' : 'Failed to clear template cache';
						break;
					case 'refresh_css':
						$css_fixed = true;
						$files = glob(APP_ROOT.'/data/users/*');
						foreach ($files as $file) {
							unlink($file);
						}
						$user_count = Database::conn()->count("kload_users")->execute();
						$batches = ceil($user_count/5);
						for ($i = 0; $i < $batches; $i++) {
							$users = Database::conn()->select("SELECT `steamid`,`custom_css` FROM `kload_users`")->where("`custom_css` != NULL OR `custom_css` != ''")->limit(5, $i*5)->execute();
							$users = (array)$users;
							foreach ($users as $user) {
								if (!empty($user['custom_css'])) {
									file_put_contents(APP_ROOT.'/data/users/'.$user['steamid'].'.css', Util::minify($user['custom_css']));
									if (!file_exists(APP_ROOT.'/data/users/'.$user['steamid'].'.css')) {
										Util::log('action', 'Failed to recreate CSS file for User: '.$user['steamid']);
										$css_fixed = false;
									} else {
										Util::log('action', 'Recreated CSS file for User: '.$user['steamid']);
									}
								}
							}
						}
						$data['alert'] = $css_fixed ? 'Player\'s CSS have been recompiled' : 'Failed to recompile all users, check the logs';
						break;
					case 'unban_all':
						$success = Database::conn()->add("UPDATE `kload_users` SET `banned` = 0")->execute();
						$data['alert'] = $success ? 'All users have been unbanned' : 'Failed to unban all users';
						break;
					case 'reset_perms':
						$success = Database::conn()->add("UPDATE `kload_users` SET `admin` = 0, `perms` = '[]' WHERE `steamid` != '?'", [$_SESSION['steamid']])->execute();
						$data['alert'] = $success ? 'Perms have been reset for all users' : 'Failed to reset perms';
						break;
					default:
						$data['alert'] = 'Not a valid action';
						break;
				}
			}
		}

		Template::render('@admin/index.twig', $data);
	}

	public static function general() {
		$perms = $_SESSION['perms'];

		if (!array_key_exists('community_name', $perms) && !array_key_exists('backgrounds', $perms) && !array_key_exists('description', $perms) && !array_key_exists('youtube', $perms) && !User::isSuper($_SESSION['steamid'])) {
			Util::redirect('/dashboard');
		}

		if (isset($_POST['save']) && isset($_SESSION['steamid'])) {
			$_POST['backgrounds']['enable'] = (isset($_POST['backgrounds']['enable']) ? (int)$_POST['backgrounds']['enable'] : 0);
			$_POST['backgrounds']['random'] = (isset($_POST['backgrounds']['random']) ? (int)$_POST['backgrounds']['random'] : 0);
			$_POST['backgrounds']['duration'] = (isset($_POST['backgrounds']['duration']) && $_POST['backgrounds']['duration'] != 0) ? (int) $_POST['backgrounds']['duration'] : 8000;
			$_POST['backgrounds']['fade'] = (isset($_POST['backgrounds']['duration']) && $_POST['backgrounds']['fade'] != 0) ? (int) $_POST['backgrounds']['fade'] : 750;

			$_POST['youtube']['enable'] = (isset($_POST['youtube']['enable']) ? (int)$_POST['youtube']['enable'] : 0);
			$_POST['youtube']['random'] = (isset($_POST['youtube']['random']) ? (int)$_POST['youtube']['random'] : 0);
			$_POST['youtube']['volume'] = (isset($_POST['youtube']['volume']) ? (int)$_POST['youtube']['volume'] : 0);
			$_POST['youtube']['list'] = (isset($_POST['youtube']['list']) ? $_POST['youtube']['list'] : []);
			if (count($_POST['youtube']['list']) > 0) {
				$yt_ids = [];
					foreach ($_POST['youtube']['list'] as $url) {
					$url = trim($url);
					$youtube_id = Util::YouTubeID($url);
					if ($youtube_id) {
						$yt_ids[] = $youtube_id;
					}
				}
				$_POST['youtube']['list'] = $yt_ids;
			}

			$success = Util::updateSetting(['backgrounds', 'community_name', 'description', 'youtube'], [$_POST['backgrounds'], (isset($_POST['community_name']) ? $_POST['community_name'] : ''), (isset($_POST['description']) ? substr($_POST['description'], 0, 250) : ''), $_POST['youtube']], $_POST['csrf']);
			if ($success) {
				Cache::store('settings', Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages', 'music'), 0);
			}
			$alert = ($success ? 'Save successful' : 'Failed to save, please try again');
		}

		$data = [
			'settings' => Util::getSetting('backgrounds', 'community_name', 'description', 'youtube'),
			'alert' => (isset($alert) ? $alert : '')
		];
		$data['settings']['backgrounds'] = json_decode($data['settings']['backgrounds'], true);
		$data['settings']['youtube'] = json_decode($data['settings']['youtube'], true);

		Template::render('@admin/general.twig', $data);
	}

	public static function rules() {
		$perms = $_SESSION['perms'];
		if (!array_key_exists('rules', $perms) && !User::isSuper($_SESSION['steamid'])) {
			Util::redirect('/dashboard');
		}

		if (isset($_POST['save']) && isset($_POST['rules'])) {
			$success = Util::updateSetting(['rules'], [$_POST['rules']], $_POST['csrf']);
			if ($success) {
				Cache::store('settings', Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages', 'music'), 0);
			}
			$alert = ($success ? 'Rules have been saved' : 'Failed to save, please try again');
		}

		$data = [
			'settings' => Util::getSetting('rules'),
			'alert' => (isset($alert) ? $alert : '')
		];
		$data['settings']['rules'] = json_decode($data['settings']['rules'], true);

		Template::render('@admin/rules.twig', $data);
	}

	public static function messages() {
		$perms = $_SESSION['perms'];
		if (!array_key_exists('messages', $perms) && !User::isSuper($_SESSION['steamid'])) {
			Util::redirect('/dashboard');
		}

		if (isset($_POST['save']) && isset($_POST['messages'])) {
			$_POST['messages']['duration'] = (isset($_POST['messages']['duration']) ? (int)$_POST['messages']['duration'] : 5000);
			$_POST['messages']['fade'] = (isset($_POST['messages']['fade']) ? (int)$_POST['messages']['fade'] : 500);
			$success = Util::updateSetting(['messages'], [$_POST['messages']], $_POST['csrf']);
			if ($success) {
				Cache::store('settings', Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages', 'music'), 0);
			}
			$alert = ($success ? 'Messages have been saved' : 'Failed to save, please try again');
		}

		$data = [
			'settings' => Util::getSetting('messages'),
			'alert' => (isset($alert) ? $alert : '')
		];
		$data['settings']['messages'] = json_decode($data['settings']['messages'], true);

		Template::render('@admin/messages.twig', $data);
	}

	public static function staff() {
		$perms = $_SESSION['perms'];
		if (!array_key_exists('staff', $perms) && !User::isSuper($_SESSION['steamid'])) {
			Util::redirect('/dashboard');
		}

		if (isset($_POST['save']) && isset($_POST['staff'])) {
			foreach ($_POST['staff'] as $gamemode => $ranks) {
				$_POST['staff'][$gamemode] = array_values($ranks);
			}
			$success = Util::updateSetting(['staff'], [$_POST['staff']], $_POST['csrf']);
			if ($success) {
				Cache::store('settings', Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages', 'music'), 0);
			}
			$alert = ($success ? 'Staff have been saved' : 'Failed to save, please try again');
		}
		else if (isset($_POST['save']) && !isset($_POST['staff'])) {
			$success = Util::updateSetting(['staff'], ['[]'], $_POST['csrf']);
			if ($success) {
				Cache::store('settings', Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages', 'music'), 0);
			}
			$alert = ($success ? 'Staff have been saved' : 'Failed to save, please try again');
		} else {}

		$data = [
			'settings' => Util::getSetting('staff'),
			'alert' => (isset($alert) ? $alert : '')
		];
		$data['settings']['staff'] = json_decode($data['settings']['staff'], true);

		Template::render('@admin/staff.twig', $data);
	}

	public static function music()
	{
		$perms = $_SESSION['perms'];
		if (!array_key_exists('music', $perms) && !User::isSuper($_SESSION['steamid'])) {
			Util::redirect('/dashboard');
		}

		if (isset($_POST['save']) && isset($_POST['music'])) {
			// validate basic shit
			$_POST['music']['volume'] = (int) ($_POST['music']['volume'] ?? 15);
			$_POST['music']['enable'] = (int) ($_POST['music']['enable'] ?? 0);
			$_POST['music']['random'] = (int) ($_POST['music']['random'] ?? 0);
			$_POST['music']['source'] = isset($_POST['music']['source']) ? (in_array($_POST['music']['source'], ['youtube', 'files', 'soundcloud']) ? $_POST['music']['source'] : 'youtube') : 'youtube';


			// validate youtube shit
			if (!isset($_POST['youtube']['list'])) {
				$_POST['youtube']['list'] = [];
			} else {
				// we only need the list now reeee
				$_POST['youtube'] = array_intersect_key($_POST['youtube'], ['list' => []]);
			}

			if (count($_POST['youtube']['list']) > 0) {
				$yt_ids = [];
				foreach ($_POST['youtube']['list'] as $url) {
					$url = trim($url);
					$youtube_id = Util::YouTubeID($url);
					if ($youtube_id) {
						$yt_ids[] = $youtube_id;
					}
				}
				$_POST['youtube']['list'] = $yt_ids;
			}

			// validate music file order
			if (!isset($_POST['music']['order'])) {
				$_POST['music']['order'] = [];
			}

			$_POST['music']['order'] = array_unique($_POST['music']['order']);

			if (($orderLength = count($_POST['music']['order'])) > 0) {
				$musicFileOrder = $_POST['music']['order'];

				for ($i = 0; $i < $orderLength; $i++) {
					if (!file_exists(APP_ROOT.'/data/music/'.$musicFileOrder[$i])) {
						unset($musicFileOrder[$i]);
					}
				}

				$_POST['music']['order'] = array_values($musicFileOrder);
			}

			$success = Util::updateSetting(['music', 'youtube'], [$_POST['music'], $_POST['youtube']], $_POST['csrf']);
			if ($success) {
				Cache::store('settings', Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages', 'music'), 0);
			}
			$alert = ($success ? 'Music settings have been saved' : 'Failed to save, please try again and check the data/logs if necessary');

			Util::flash('alert', $alert);
			Util::redirect('/dashboard/admin/music');
		}

		$data = Util::getSetting('music', 'youtube');
		if (!isset($data['music'])) {
			$temp_music_data = [
				'source' => 'youtube',
				'random' => $data['youtube']['volume'] ?? 0,
				'enable' => $data['youtube']['volume'] ?? 0,
				'volume' => $data['youtube']['volume'] ?? 15,
			];

			$success = Util::updateSetting(['music'], [$temp_music_data], null, true);

			if (!$success) {
				throw new \Exception('Failed to implement new music data. Please check the mysql error logs in data/logs/mysql');
			}

			$data['music'] = $temp_music_data;
		} else {
			$data['music'] = json_decode($data['music'], true);
		}
		$data['youtube'] = json_decode($data['youtube'], true);

		$dir = APP_ROOT.'/data/music';
		$files = glob($dir.'/*.ogg');
		$length = count($files);

		if ($length > 0) {
			for ($i = 0; $i < $length; $i++) {
				$filename = str_replace($dir.'/', '', $files[$i]);
				$name = str_replace('.ogg', '', $filename);

				$files[$i] = [
					'filename' => $filename,
					'name' => $name,
					'name_hashed' => md5($name),
					'row_id' => 'file_'.md5($name),
					'url' => APP_URL.'/data/music/'.$filename
				];
			}
		}

		$data['music_files'] = $files;

		Template::render('@admin/music.twig', $data);
	}

	public static function musicUpload()
	{
		if (!isset($_SESSION['steamid'])) {
			die();
		}

		$perms = $_SESSION['perms'];
		if (!array_key_exists('music', $perms) && !User::isSuper($_SESSION['steamid'])) {
			die();
		}

		$success = false;
		$message = 'No/Invalid file sent';
		$file = [];

		if (isset($_FILES['music_file'])) {
			switch ($_FILES['music_file']['error']) {
				case UPLOAD_ERR_NO_FILE:
					$message = 'No file sent';
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$message = 'Filesize limit exceeded';
					break;
				case UPLOAD_ERR_OK:
					$fileinfo = new \finfo(FILEINFO_MIME_TYPE);

					if ($fileinfo->file($_FILES['music_file']['tmp_name']) !== 'audio/ogg') {
						$message = 'Invalid filetype uploaded. Must be "audio/ogg"';
						break;
					}

					$filename = preg_replace('/[[:^print:]]/', '', $_FILES['music_file']['name']);
					$name = str_replace('.ogg', '', $filename);

					$success = self::storeMusic($_FILES['music_file'], $name);
					$message = $success ? 'File uploaded successfully' : 'Failed to upload file, check file permissions or try again';
					if ($success) {
						$file = [
							'filename' => $filename,
							'name' => $name,
							'name_hashed' => md5($name)
						];
					}
					break;
				default:
					$message = 'Unknown error, check your upload_max_filesize and post_max_size in your php.ini';
					break;
			}
		}

		Util::json(['success' => $success, 'message' => $message, 'file' => $file], true);
	}

	private static function storeMusic($file, $filename = null)
	{
		if (is_null($filename)) {
			$filename = preg_replace('/[[:^print:]]/', '', $file['name']);
			$filename = str_replace('.ogg', '', $filename);
		}

		Util::mkDir(APP_ROOT.'/data/music');

		return move_uploaded_file($file['tmp_name'], APP_ROOT.'/data/music/'.$filename.'.ogg');
	}

	public static function deleteMusic()
	{
		if (!isset($_SESSION['steamid'])) {
			die();
		}

		$perms = $_SESSION['perms'];
		if (!array_key_exists('music', $perms) && !User::isSuper($_SESSION['steamid'])) {
			die();
		}

		$filename = isset($_POST['file']) ? $_POST['file'] : null;
		$success = false;
		$message = 'Failed to delete. Try again';

		$filepath = '/data/music/'.$filename;
		if (!empty($filename) && file_exists(APP_ROOT.'/data/music/'.$filename)) {
			$success = unlink(APP_ROOT.$filepath);
			$message = $success ? 'File deleted' : $message;
		} else {
			$message = 'File does not exist, cannot delete';
		}

		Util::json(['success' => $success, 'message' => $message], true);
	}
}
