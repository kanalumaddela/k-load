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

namespace KLoad\Controllers\Admin;

use KLoad\Controllers\AdminController;

class General extends AdminController
{
    protected static string $templateFolder = 'general';

    public function boot()
    {
        static::$templateFolder = parent::$templateFolder . '/' . static::$templateFolder;
    }

    public function index()
    {
        return $this->view('index');
    }

    public function core()
    {
        return 'test';
    }

    public function general()
    {

    }
}