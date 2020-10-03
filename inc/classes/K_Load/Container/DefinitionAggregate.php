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

namespace K_Load\Container;

use League\Container\Definition\DefinitionInterface;

class DefinitionAggregate extends \League\Container\Definition\DefinitionAggregate
{
    public function add(string $id, $definition, bool $shared = false): DefinitionInterface
    {
        if (!$definition instanceof DefinitionInterface) {
            $definition = new Definition($id, $definition);
        }

        $this->definitions[] = $definition
            ->setAlias($id)
            ->setShared($shared);

        return $definition;
    }
}