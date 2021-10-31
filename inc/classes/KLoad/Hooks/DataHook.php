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

namespace KLoad\Hooks;

abstract class DataHook implements DataHookInterface
{


    protected array $data = [];

    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    public function setData(array $data = []): DataHook
    {
        $this->data = $data;

        return $this;
    }
}
