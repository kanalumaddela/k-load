<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2020 Maddela
 * @license   MIT
 */

//if ($_SERVER['SERVER_NAME'] === 'demo.maddela.org') {
//    die('making this better, have patience thx');
//}
declare(strict_types=1);

use const K_Load\APP_ROOT;

define('K_Load\\'.'APP_START', microtime(true));

require_once __DIR__.'/vendor/autoload.php';
die();

// fuck people and shit hosts
$display_info = false;
$display_info_messages = [];

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
if (PHP_VERSION_ID < 70200) {
    $display_info = true;

    $display_info_messages[] = <<<EOT
    <div style="text-align: center">
    <h1 style="color: red;text-transform: uppercase"><mark>PHP 7.2 and up</mark> is required<br>see if your host can upgrade or get a better host<h1>
    </div>
EOT;
}

// better be at least 64 bit before checking version
if (PHP_INT_SIZE === 4) {
    $display_info = true;
    $display_info_messages[] = '<div style="color:red;text-align:center;"><h4 style="text-transform:uppercase">You are running the 32 bit version of PHP, 64 bit is required and should be the standard in this modern age</h4></div>';
}

if ($display_info) {
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">';

    echo implode('', $display_info_messages).'<hr><br>';

    ob_start();
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES | INFO_ENVIRONMENT | INFO_VARIABLES);
    $phpinfo = ob_get_contents();
    ob_end_clean();
    $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
    echo $phpinfo;
    echo '<style>body{width:90%;max-width:1200px;margin:auto;background:#06111f;color:#e0e0e0}table{display:block;padding:15px;overflow:auto;background-color:rgba(0,0,0,0.1);}</style>';
    die();
}

unset($display_info, $display_info_messages);


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


unset($check);
unset($version);

// everything else
define('K_Load\\'.'APP_ROOT', __DIR__);

require_once APP_ROOT.'/inc/handlers.php';
//require_once APP_ROOT.'/inc/error.php';
require_once APP_ROOT.'/inc/init.php';
