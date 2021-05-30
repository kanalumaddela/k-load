<?php
/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 kanalumaddela
 * @license   MIT
 */

//if ($_SERVER['SERVER_NAME'] === 'demo.maddela.org') {
//    die('making this better, have patience thx');
//}
declare(strict_types=1);

define('KLoad\\' . 'APP_START', microtime(true));

require_once __DIR__.'/vendor/autoload.php';