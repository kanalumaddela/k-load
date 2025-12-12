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

namespace KLoad\Casts;

use function array_flip;
use function dump;
use function json_decode;
use function json_encode;

class JsonToFlippedArray
{
    public function get($model, string $key, $value, array $attributes)
    {
        dump('tbh');

        return array_flip(json_decode($value, true));
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return json_encode($value);
    }
}
