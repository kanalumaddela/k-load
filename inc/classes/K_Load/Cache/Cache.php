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

namespace K_Load\Cache;

use InvalidArgumentException;
use function call_user_func;
use function intval;
use function is_array;
use function is_callable;
use function is_int;
use function is_null;
use function is_numeric;
use function is_string;
use function max;
use function Opis\Closure\{serialize as opis_serialize, unserialize as opis_unserialize};

class Cache extends \J0sh0nat0r\SimpleCache\Cache
{
    public function store($key, $value = null, $time = null)
    {
        $this->validateKey($key);

        if (!is_int($time) && !is_null($time)) {
            throw new InvalidArgumentException('`time` must be an integer or null');
        }

        $time = is_null($time) ? self::$DEFAULT_TIME : max(0, $time);

        if (is_array($key)) {
            $time = is_null($value) ? $time : $value;
            $values = $key;

            $successes = [];
            foreach ($values as $key => $value) {
                $successes[$key] = $this->store($key, $value, $time);
            }

            return $successes;
        }

        if (!is_numeric($time)) {
            throw new InvalidArgumentException('Time must be numeric');
        }

        $success = $this->driver->put($key, opis_serialize($value), intval($time));

        if ($success && $this->remember_values) {
            $this->loaded[$key] = $value;
        }

        return $success;
    }

    public function get($key, $default = null)
    {
        $this->validateKey($key);

        if (is_array($key)) {
            $keys = $key;

            $results = [];
            foreach ($keys as $key) {
                $results[$key] = $this->get($key, $default);
            }

            return $results;
        }

        if ($this->remember_values && isset($this->loaded[$key])) {
            return $this->loaded[$key];
        }

        $result = $this->driver->get($key);

        if (is_null($result)) {
            if (is_callable($default) && !is_string($default)) {
                return call_user_func($default, $key);
            }

            return $default;
        }

        $result = opis_unserialize($result);

        if ($this->remember_values) {
            $this->loaded[$key] = $result;
        }

        return $result;
    }
}
