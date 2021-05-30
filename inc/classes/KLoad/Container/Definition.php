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

namespace KLoad\Container;

use ReflectionClass;
use ReflectionException;

class Definition extends \League\Container\Definition\Definition
{
    public function addTags(...$tags)
    {
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }

        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function autoTag()
    {
        $reflection = new ReflectionClass($this->concrete);
        $this->addTag($reflection->getShortName());

        return $this;
    }
}
