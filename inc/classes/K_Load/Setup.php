<?php

namespace K_Load;

use Database;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use Steam;
use ZipArchive;

class Setup {

	public static function install($config) {
		Cache::remove('version');
		Database::disconnect();
		Database::clear();

		if (Util::installed()) {
			Util::redirect('/dashboard/admin');
		}

		file_put_contents(APP_ROOT.'/data/config.php', '<?php'."\n".'return '.Util::var_export($config).';'."\n".'?>');
		$config = include APP_ROOT.'/data/config.php';

		Steam::Key($config['apikeys']['steam']);
		Database::connect($config['mysql']);

		Cache::remove('version');
		$migrations = glob(APP_ROOT.sprintf('%sinc%smigrations%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
		foreach ($migrations as $folder) {
			$path_arr = explode(DIRECTORY_SEPARATOR, $folder);
			$ver = end($path_arr);
			$tmp_version = (int)str_replace('.', '', $ver);
			file_exists($folder.'/drop.php') AND include $folder.'/drop.php';
			file_exists($folder.'/delete.php') AND include $folder.'/delete.php';
			file_exists($folder.'/create.php') AND include $folder.'/create.php';
			file_exists($folder.'/alter.php') AND include $folder.'/alter.php';
			file_exists($folder.'/insert.php') AND include $folder.'/insert.php';
			Cache::remove('version');
			$installed = Util::version() == $ver;
			Util::log('action', 'K-Load v'.$ver.($installed ? ' was' : ' failed to').' installed');
		}

		Cache::clear();

		User::add($_SESSION['steamid']);
		User::session($_SESSION['steamid']);

		Util::log('action', 'K-Load has been installed', true);
		Util::redirect('/dashboard/admin');
	}

	public static function update() {
		if (User::isSuper($_SESSION['steamid'])) {
			Cache::remove('version');
			$version = (int)str_replace('.','',Util::version());
			$migrations = glob(APP_ROOT.sprintf('%sinc%smigrations%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
			foreach ($migrations as $folder) {
				$path_arr = explode(DIRECTORY_SEPARATOR, $folder);
				$ver = end($path_arr);
				$tmp_version = (int)str_replace('.','',$ver);

				if ($tmp_version > $version) {
				    file_exists($folder.'/drop.php') AND include $folder.'/drop.php';
					file_exists($folder.'/delete.php') AND include $folder.'/delete.php';
					file_exists($folder.'/create.php') AND include $folder.'/create.php';
					file_exists($folder.'/alter.php') AND include $folder.'/alter.php';
					file_exists($folder.'/insert.php') AND include $folder.'/insert.php';

					Cache::remove('version');
					$installed = Util::version() == $ver;
					Util::log('action', 'K-Load v'.$ver.($installed ? ' was' : ' failed to').' installed');
				}
			}

			Cache::clear();
		}
	}

	public static function getUpdates() {
		$updates = [
			'amount' => 0,
			'latest' => ''
		];

		$version = (int)str_replace('.','',Util::version());
		$migrations = glob(APP_ROOT.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
		foreach ($migrations as $folder) {
			$path_arr = explode(DIRECTORY_SEPARATOR, $folder);
			$ver = end($path_arr);
			$tmp_version = (int)str_replace('.','',$ver);

			if ($tmp_version > $version) {
				$updates['amount']++;
				$updates['latest'] = $ver;
			}
		}

		return $updates;
	}

	public static function phpinfo() {
		if (!Util::installed() || User::isAdmin()) {
			phpinfo();
			die();
		} else {
			Util::redirect(APP_PATH);
		}
	}

}
