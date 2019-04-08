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

Database::run("UPDATE `kload_settings` SET `value` = '0.1.7' WHERE `name` = 'version'");
