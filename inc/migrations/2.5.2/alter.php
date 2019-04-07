<?php

use K_Load\Util;

Database::run("UPDATE `kload_settings` SET `value` = '2.5.2' WHERE `name` = 'version'");

$settings = Util::getSetting('messages');
$settings['messages'] = json_decode($settings, true);
if (!isset($settings['messages']['list'])) {
    $settings['messages']['list'] = [];
    Util::updateSetting(['messages'], [$settings['messages']], null, true);
}
