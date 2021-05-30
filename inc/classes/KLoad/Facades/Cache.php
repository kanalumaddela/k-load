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

namespace KLoad\Facades;

/**
 * Cache.
 *
 * @method static bool|bool[] store($key, $value = null, $time = null) Store a value ( or an array of key-value pairs)
 *                                                                                                                     in the cache.
 * @method static bool|bool[] forever($key, $value = null) Store a item ( or an array of items)                        in the cache
 *                                                                                                                     indefinitely.
 * @method static mixed remember($key, $time, $generate, $default = null)                                              Try to find a value in the cache and return
 *                                                                                                                     it, if we can't it will be calculated with the provided closure.
 * @method static bool|bool[] has($key)                                                                                Check if the cache contains an item.
 * @method static mixed get($key, $default = null) Fetch a value ( or an multiple values)                              from the cache.
 * @method static mixed pull($key, $default = null) Fetch an item ( or multiple items)                                 from the cache, then remove it.
 * @method static bool|bool[] remove($key) Remove an item ( or multiple items)                                         from the cache.
 * @method static bool clear()                                                                                         Clears the cache, removing ALL items.
 */
class Cache extends Facade
{
}
