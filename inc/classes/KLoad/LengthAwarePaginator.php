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

namespace KLoad;

class LengthAwarePaginator extends \Illuminate\Pagination\LengthAwarePaginator
{
    /**
     * @var array
     */
    protected $pageList = [];

    public function pageList()
    {
        if (empty($this->pageList)) {
            $this->pageList = $this->elements();
        }

        return $this->pageList;
    }
}
