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

use K_Load\Util;

Database::run("UPDATE `kload_settings` SET `value` = '2.6.0' WHERE `name` = 'version'");

// convert to spaces hehe
$config = include_once APP_ROOT.'/data/config.php';
file_put_contents(APP_ROOT.'/data/config.php', '<?php'."\n".'return '.Util::var_export($config).';'."\n");

$settings = Util::getSetting('music');

if (isset($settings['music'])) {
    $settings['music'] = json_decode($settings['music'], true);

    if (count($settings['music']['order']) > 0) {
        $keys = array_keys($settings['music']['order']);

        if (is_numeric($keys[0])) {
            $settings['music']['order'] = ['global' => $settings['music']['order']];
            Util::saveSetting('music', $settings['music']);
        }
    }
}