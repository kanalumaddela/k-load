<?php
/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2023 kanalumaddela
 * @license   MIT
 */

namespace KLoad\Controllers\Admin;

use KLoad\Controllers\AdminController;
use KLoad\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

class Backgrounds extends AdminController
{
    protected static array $defaultData = [
        'enable'   => true,
        'random'   => true,
        'duration' => 5000,
        'fade'     => 500,
    ];

    public function index(): Response
    {
        $settings = Setting::where('name', 'backgrounds')->pluck('value', 'name');

//        $test = symlink(APP_ROOT.'/assets/img/backgrounds/global/particles_red.jpg', APP_ROOT.'/assets/img/backgrounds/test');
//
//        dd($test);

        return $this->view('index', get_defined_vars());
    }
}
