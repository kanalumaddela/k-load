<?php

// transfer misc youtube options to music options instead since they apply to both
$yt_settings = \K_Load\Util::getSetting('youtube');
$yt_settings = $music_settings = json_decode($yt_settings['youtube'], true);

unset($yt_settings['enable']);
unset($yt_settings['random']);
unset($yt_settings['volume']);
unset($music_settings['list']);
$music_settings['order'] = [];

\K_Load\Util::updateSetting(['music', 'youtube'], [$music_settings, $yt_settings], null, true);

Database::run("UPDATE `kload_settings` SET `value` = '0.2.0' WHERE `name` = 'version'");
