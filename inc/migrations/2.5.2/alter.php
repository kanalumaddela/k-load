<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/)
 *
 * @link https://www.maddela.org
 * @link https://github.com/kanalumaddela/k-load-v2
 *
 * @author kanalumaddela <git@maddela.org>
 *
 * @copyright Copyright (c) 2018-2019 Maddela
 *
 * @license MIT
 */

use K_Load\Util;

Database::run("UPDATE `kload_settings` SET `value` = '2.5.2' WHERE `name` = 'version'");

$settings = Util::getSetting('messages');
$settings['messages'] = json_decode($settings, true);
if (!isset($settings['messages']['list'])) {
    $settings['messages']['list'] = [];
    Util::updateSetting(['messages'], [$settings['messages']], null, true);
}
