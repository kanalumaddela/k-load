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

namespace KLoad\Traits;

use Illuminate\Database\Eloquent\Concerns\HasAttributes;

trait HasCustomCastsAttributes
{
    use HasAttributes {
        HasAttributes::castAttribute as parentCastAttribute;
    }

    protected static array $castsAttributeClassCache = [];

    protected array $castsAttributeCache = [];

    protected function castAttribute($key, $value)
    {
        if (isset($this->castsAttributeCache[$key])) {
            return $this->castsAttributeCache[$key];
        }

        $newValue = $this->parentCastAttribute($key, $value);

        if ($newValue === $value) {
            $type = $this->getCastType($key);

            if (\class_exists($type)) {
                if (!isset(static::$castsAttributeClassCache[$type])) {
                    static::$castsAttributeClassCache[$type] = new $type();
                }

                $newValue = static::$castsAttributeClassCache[$type]->get(\get_class($this), $key, $value, $this->getAttributes());
            }
        }

        $this->castsAttributeCache[$key] = $newValue;

        return $newValue;
    }
}
