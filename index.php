<?php

// debug time
$start = microtime(true);

// data/constants.php
include __DIR__.'/data/constants.php';

// show errors
if (DEBUG) {
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
	echo "<h1>php 7 and up is required, see if your host can upgrade or get a better host<h1><br><hr><br>";
	phpinfo();
	die();
}

// test write perms, doing it this early cause retards
if (!file_exists(__DIR__ . '/data/FILE_WRITE_CHECK_DO_NOT_REMOVE')) {
	set_error_handler(function () {});
	$check = mkdir(__DIR__ . '/test', 0775, true);
	restore_error_handler();

	if (!$check && !file_exists(__DIR__ . '/test')) {
		echo '<div style="color:red;text-align:center;"><h1 style="text-transform:uppercase">insufficient permissions to write</h1><h3>before submitting a ticket, try:</h3>';
		echo '<code style="color:#0ed60e;background:black;padding: 5px 3px;">chown -R www-data:www-data /var/www/html</code><p>or whatever the path is to your files</p>';
		echo '</div>';
		phpinfo();
		die();
	}
	rmdir(__DIR__ . '/test');
	if (!file_exists(__DIR__ . '/data')) {
		mkdir(__DIR__ . '/data');
	}
	touch(__DIR__ . '/data/FILE_WRITE_CHECK_DO_NOT_REMOVE');
}

// another check for retard setups/hosts
if (!function_exists('curl_init') || !extension_loaded('curl')) {
	echo '<div style="color:red;text-align:center;"><h1 style="text-transform:uppercase">the php extension "curl" is not loaded/enabled/installed. without it you cannot login</h1><h3>if you are using shared hosting contact them to enable it</h3>';
	echo '</div>';
	phpinfo();
	die();
}

// everything else
define('APP_ROOT', __DIR__);
require_once __DIR__.'/inc/init.php';