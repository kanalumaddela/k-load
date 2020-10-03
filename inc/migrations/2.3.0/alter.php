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
if (file_exists(APP_ROOT.'/themes/default/preview.png')) {
    unlink(APP_ROOT.'/themes/default/preview.png');
}
if (file_exists(APP_ROOT.'/themes/magnus/chrome_2018-02-20_20-53-17.jpg')) {
    unlink(APP_ROOT.'/themes/magnus/chrome_2018-02-20_20-53-17.jpg');
}
if (file_exists(APP_ROOT.'/themes/magnus/preview.jpg')) {
    unlink(APP_ROOT.'/themes/magnus/preview.jpg');
}
if (file_exists(APP_ROOT.'/themes/metra/chrome_2018-04-01_17-32-10.png')) {
    unlink(APP_ROOT.'/themes/metra/chrome_2018-04-01_17-32-10.png');
}
if (file_exists(APP_ROOT.'/themes/oxygen/preview.png')) {
    unlink(APP_ROOT.'/themes/oxygen/preview.png');
}
if (file_exists(APP_ROOT.'/themes/neuron/preview.png')) {
    unlink(APP_ROOT.'/themes/neuron/preview.png');
}
if (file_exists(APP_ROOT.'/themes/slick/chrome_2018-04-02_12-52-50.png')) {
    unlink(APP_ROOT.'/themes/slick/chrome_2018-04-02_12-52-50.png');
}

Database::run("UPDATE `kload_settings` SET `value` = '2.3.0' WHERE `name` = 'version'");
