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
Database::run("UPDATE `kload_settings` SET `value` = '2.5.0' WHERE `name` = 'version'");

@unlink(APP_ROOT.'/assets/js/site.js');
@unlink(APP_ROOT.'/themes/metra/assets/js/metra.js');
