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

namespace K_Load\Controllers\Admin;


use K_Load\Controllers\AdminController;
use K_Load\Util;

class Themes extends AdminController
{
    public static $templateFolder = 'admin/themes';


    public function index()
    {
        return self::view('index');
    }

    public function edit($theme = null)
    {
        if (empty($theme)) {
            Util::flash('alert', 'No theme given');
            Util::redirect('/dashboard/admin/themes');
        }

        return self::view('edit', ['themeFiles' => directoryTree(APP_ROOT.'/themes/default')]);
    }

}