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

namespace K_Load;

use InvalidArgumentException;
use function explode;
use function gettype;
use function is_array;

class OldDotArray
{
    /**
     * @var array
     */
    protected $data = [];

    public function __construct()
    {
    }

    public function set(string $key, $value)
    {
        $arr = &$this->data;
        $keyString = '';

        foreach (explode('.', $key) as $k) {
            $keyString .= empty($keyString) ? $k : '.'.$k;

            if (!isset($arr[$k])) {
                $arr[$k] = [];
            }

            if (!is_array($arr[$k])) {
                throw new InvalidArgumentException('Cannot set existing key: `'.$keyString.'` due to being a ('.gettype($arr[$k]).'). Must be an (array).');
            }
        }
    }
}