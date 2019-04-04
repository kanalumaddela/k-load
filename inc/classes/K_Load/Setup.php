<?php

namespace K_Load;

use Database;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use Steam;

class Setup
{
    public static function install($config)
    {
        Cache::clear();
        Database::clear();

        if (Util::installed()) {
            Util::redirect('/dashboard/admin');
        }

        \file_put_contents(APP_ROOT.'/data/config.php', '<?php'."\n".'return '.Util::var_export($config).';'."\n".'?>');

        Steam::Key($config['apikeys']['steam']);
        $config = include APP_ROOT.'/data/config.php';
        Database::connect($config['mysql']);

        $migrations = \glob(APP_ROOT.\sprintf('%sinc%smigrations%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
        foreach ($migrations as $folder) {
            $path_arr = \explode(DIRECTORY_SEPARATOR, $folder);
            $ver = \end($path_arr);
            \file_exists($folder.'/drop.php') and include $folder.'/drop.php';
            \file_exists($folder.'/delete.php') and include $folder.'/delete.php';
            \file_exists($folder.'/create.php') and include $folder.'/create.php';
            \file_exists($folder.'/alter.php') and include $folder.'/alter.php';
            \file_exists($folder.'/insert.php') and include $folder.'/insert.php';
            $installed = Util::version(true) == $ver;
            Util::log('action', 'K-Load v'.$ver.($installed ? ' was' : ' failed to').' installed');
        }

        Cache::clear();

        \session_destroy();
        unset($_SESSION);

        Util::log('action', 'K-Load has been installed', true);
        die('K-Load has been installed. Visit <a href="'.APP_PATH.'/dashboard/admin">'.APP_PATH.'/dashboard/admin</a>');
    }

    public static function update()
    {
        if (User::isSuper($_SESSION['steamid'])) {
            Cache::remove('version');
            $version = (int) \str_replace('.', '', Util::version(true));
            $migrations = \glob(APP_ROOT.\sprintf('%sinc%smigrations%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
            foreach ($migrations as $folder) {
                $path_arr = \explode(DIRECTORY_SEPARATOR, $folder);
                $ver = \end($path_arr);
                $tmp_version = (int) \str_replace('.', '', $ver);

                if ($tmp_version > $version) {
                    \file_exists($folder.'/drop.php') and include $folder.'/drop.php';
                    \file_exists($folder.'/delete.php') and include $folder.'/delete.php';
                    \file_exists($folder.'/create.php') and include $folder.'/create.php';
                    \file_exists($folder.'/alter.php') and include $folder.'/alter.php';
                    \file_exists($folder.'/insert.php') and include $folder.'/insert.php';

                    $installed = Util::version() == $ver;
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

        $version = (int) \str_replace('.', '', Util::version());
        $migrations = \glob(APP_ROOT.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
        foreach ($migrations as $folder) {
            $path_arr = \explode(DIRECTORY_SEPARATOR, $folder);
            $ver = \end($path_arr);
            $tmp_version = (int) \str_replace('.', '', $ver);

            if ($tmp_version > $version) {
                $updates['amount']++;
                $updates['latest'] = $ver;
            }
        }

        return $updates;
    }
}
