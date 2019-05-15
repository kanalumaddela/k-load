<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

namespace K_Load;

use Database;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use Steam;
use function end;
use function explode;
use function file_exists;
use function file_put_contents;
use function glob;
use function session_destroy;
use function sprintf;
use function str_replace;
use function var_dump;

class Setup
{
    public static function install($config)
    {
        Cache::clear();
        Database::clear();

        $config['loading_theme'] = 'default';
        file_put_contents(APP_ROOT.'/data/config.php', '<?php'."\n".'return '.Util::var_export($config).';'."\n");

        if (!empty(Util::version(true)) && !isset($_SESSION['force_install'])) {
            echo '<h1>A K-Load installation already exists in this database, to force an install, refresh this page and re</h1>';
            echo 'Otherwise go to the dashboard: <a href="'.APP_PATH.'/dashboard/admin">'.APP_PATH.'/dashboard/admin</a>';
            $_SESSION['force_install'] = 1;
            die();
        }

        if (!file_exists(APP_ROOT.'/data/config.php')) {
            echo '<h2>failed to create data/config.php</h2>';
            echo 'config given:';
            echo '<pre>';
            var_dump($config);
            echo '</pre>';
            die();
        }

        Steam::Key($config['apikeys']['steam']);
        $config = include APP_ROOT.'/data/config.php';
        Database::connect($config['mysql']);

        $migrations = glob(APP_ROOT.sprintf('%sinc%smigrations%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
        foreach ($migrations as $folder) {
            $path_arr = explode(DIRECTORY_SEPARATOR, $folder);
            $ver = end($path_arr);
            file_exists($folder.'/drop.php') and include $folder.'/drop.php';
            file_exists($folder.'/delete.php') and include $folder.'/delete.php';
            file_exists($folder.'/create.php') and include $folder.'/create.php';
            file_exists($folder.'/alter.php') and include $folder.'/alter.php';
            file_exists($folder.'/insert.php') and include $folder.'/insert.php';
            $installed = Util::version(true) == $ver;
            Util::log('action', 'K-Load v'.$ver.($installed ? ' was' : ' failed to').' installed');
        }

        User::add($_SESSION['steamid']);

        Cache::clear();

        session_destroy();
        unset($_SESSION);

        Util::log('action', 'K-Load has been installed', true);
        die('K-Load has been installed. Visit <a href="'.APP_PATH.'/dashboard/admin">'.APP_PATH.'/dashboard/admin</a>');
    }

    public static function update()
    {
        if (User::isSuper($_SESSION['steamid'])) {
            Cache::clear();
            $version = (int) str_replace('.', '', Util::version(true));
            $migrations = glob(APP_ROOT.sprintf('%sinc%smigrations%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
            foreach ($migrations as $folder) {
                $path_arr = explode(DIRECTORY_SEPARATOR, $folder);
                $ver = end($path_arr);
                $tmp_version = (int) str_replace('.', '', $ver);

                if ($tmp_version > $version) {
                    file_exists($folder.'/drop.php') and include $folder.'/drop.php';
                    file_exists($folder.'/delete.php') and include $folder.'/delete.php';
                    file_exists($folder.'/create.php') and include $folder.'/create.php';
                    file_exists($folder.'/alter.php') and include $folder.'/alter.php';
                    file_exists($folder.'/insert.php') and include $folder.'/insert.php';

                    $installed = Util::version(true) == $ver;

                    Util::log('action', 'K-Load v'.$ver.($installed ? ' was' : ' failed to').' installed');
                }
            }

            Cache::clear();
        }
    }

    public static function getUpdates()
    {
        $updates = [
            'amount' => 0,
            'latest' => '',
        ];

        $version = (int) str_replace('.', '', Util::version());
        $migrations = glob(APP_ROOT.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
        foreach ($migrations as $folder) {
            $path_arr = explode(DIRECTORY_SEPARATOR, $folder);
            $ver = end($path_arr);
            $tmp_version = (int) str_replace('.', '', $ver);

            if ($tmp_version > $version) {
                $updates['amount']++;
                $updates['latest'] = $ver;
            }
        }

        return $updates;
    }
}
