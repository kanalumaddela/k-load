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

//if ($_SERVER['SERVER_NAME'] === 'demo.maddela.org') {
//    die('making this better, have patience thx');
//}

// debug time
define('APP_START', microtime(true));

// data/constants.php
if (file_exists(__DIR__.'/data/constants.php')) {
    function defineUserConstants()
    {
        if (!file_exists(__DIR__.'/data/constants.old.php')) {

            $contents = file_get_contents(__DIR__.'/data/constants.php');
            preg_match_all("/define\('(\w+)', *(\w+)\);/", $contents, $matches, PREG_SET_ORDER);

            if (!empty($matches)) {
                copy(__DIR__.'/data/constants.php', __DIR__.'/data/constants.old.php');

                $inserts = [];

                foreach ($matches as $constantData) {
                    $inserts[] = '$'.strtolower($constantData[1]).' = '.(in_array($constantData[2], ['true', 'false']) || is_int($constantData[2]) ? $constantData[2] : '\''.$constantData[2].'\'').';';
                }

                file_put_contents(__DIR__.'/data/constants.php', '<?php'."\n\n".implode("\n", $inserts));
            } else {
                touch(__DIR__.'/data/constants.php');
            }

        }

        require_once __DIR__.'/data/constants.php';

        foreach (get_defined_vars() as $constant => $value) {
            define(strtoupper($constant), $value);
            unset($$constant);
        }
    }

    defineUserConstants();
}

// show errors
if (defined('DEBUG') && DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// fuck people and shit hosts
if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
if (PHP_VERSION_ID < 70000) {
    echo '<h1>php 7 and up is required, see if your host can upgrade or get a better host<h1><br><hr><br>';
    phpinfo();
    die();
}

// test write perms, doing it this early cause retards
if (!file_exists(__DIR__.'/data/FILE_WRITE_CHECK_DO_NOT_REMOVE')) {
    set_error_handler(function () {
    });
    $check = mkdir(__DIR__.'/test', 0775, true);
    restore_error_handler();

    if (!$check && !file_exists(__DIR__.'/test')) {
        echo '<div style="color:red;text-align:center;"><h1 style="text-transform:uppercase">insufficient permissions to write</h1><h3>before submitting a ticket, try:</h3>';
        echo '<code style="color:#0ed60e;background:black;padding: 5px 3px;">chown -R www-data:www-data /var/www/html</code><p>or whatever the path is to your files</p>';
        echo '</div>';
        phpinfo();
        die();
    }
    rmdir(__DIR__.'/test');
    if (!file_exists(__DIR__.'/data')) {
        mkdir(__DIR__.'/data');
    }
    touch(__DIR__.'/data/FILE_WRITE_CHECK_DO_NOT_REMOVE');
}

// another check for retard setups/hosts
if (!function_exists('curl_init') || !extension_loaded('curl')) {
    echo '<div style="color:red;text-align:center;"><h1 style="text-transform:uppercase">the php extension "curl" is not loaded/enabled/installed. without it you cannot login</h1><h3>if you are using shared hosting contact them to enable it</h3>';
    echo '</div>';
    phpinfo();
    die();
}
// another check for retard setups/hosts
if (PHP_INT_SIZE === 4) {
    echo '<div style="color:red;text-align:center;"><h1 style="text-transform:uppercase">You are running the 32 bit version of PHP, 64 bit is required and should be the standard in this modern age</h1>';
    echo '</div>';
    phpinfo();
    die();
}

// everything else
define('APP_ROOT', __DIR__);
require_once APP_ROOT.'/inc/init.php';
