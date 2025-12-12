<?php
/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2025 kanalumaddela
 * @license   MIT
 */

namespace KLoad\Facades;

use KLoad\App;
use function call_user_func_array;
use function str_replace;

abstract class Facade
{
    protected static $resolved = [];

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([static::getInstance(), $name], $arguments);
    }

    protected static function getInstance()
    {
        $id = static::getId();

        if (!isset(static::$resolved[$id])) {
            static::$resolved[$id] = App::get($id);
        }

        return static::$resolved[$id];
    }

    protected static function getId(): string
    {
        return str_replace(__NAMESPACE__ . '\\', '', static::class);
    }
}
