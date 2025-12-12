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

namespace KLoad\Cache;

use InvalidArgumentException;

use function Opis\Closure\serialize as opis_serialize;
use function Opis\Closure\unserialize as opis_unserialize;

class Cache extends \J0sh0nat0r\SimpleCache\Cache
{
    public function store($key, $value = null, $time = null): array|bool
    {
        $this->validateKey($key);

        if (!\is_int($time) && !\is_null($time)) {
            throw new InvalidArgumentException('`time` must be an integer or null');
        }

        $time = \is_null($time) ? self::$DEFAULT_TIME : \max(0, $time);

        if (\is_array($key)) {
            $time = \is_null($value) ? $time : $value;
            $values = $key;

            $successes = [];
            foreach ($values as $k => $v) {
                $successes[$k] = $this->store($k, $v, $time);
            }

            return $successes;
        }

        if (!\is_numeric($time)) {
            throw new InvalidArgumentException('Time must be numeric');
        }

        $success = $this->driver->put($key, opis_serialize($value), (int) $time);

        if ($success && $this->remember_values) {
            $this->loaded[$key] = $value;
        }

        return $success;
    }

    public function get($key, $default = null)
    {
        $this->validateKey($key);

        if (\is_array($key)) {
            $keys = $key;

            $results = [];
            foreach ($keys as $k) {
                $results[$k] = $this->get($k, $default);
            }

            return $results;
        }

        if ($this->remember_values && isset($this->loaded[$key])) {
            return $this->loaded[$key];
        }

        $result = $this->driver->get($key);

        if (\is_null($result)) {
            if (\is_callable($default) && !\is_string($default)) {
                return $default($key);
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
