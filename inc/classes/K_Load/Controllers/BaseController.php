<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2019 Maddela
 * @license   MIT
 */

namespace K_Load\Controllers;

use K_Load\User;
use Symfony\Component\HttpFoundation\Request;
use function str_replace;
use function view as globalView;

class BaseController
{
    public static $templateFolder = '';
    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $http;

    public function __construct()
    {
        $this->http = Request::createFromGlobals();
        $this->boot();
    }

    public function boot()
    {

    }

    public static function view($template, array $data = [])
    {
        $template = str_replace('.twig', '', $template);

        return globalView(!empty(static::$templateFolder) ? '@controllers/'.static::$templateFolder.'/'.$template : $template, $data);
    }

    public function authorize(...$perms)
    {
        if (!User::can(...$perms)) {
            return globalView('@errors/403');
        }

        return true;
    }
}
