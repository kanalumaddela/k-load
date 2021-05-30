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

use function file_get_contents;
use function is_null;

class Request extends \Symfony\Component\HttpFoundation\Request
{
    public static function createFromGlobals(): Request
    {
        $json_post = json_decode(file_get_contents('php://input'), true);

        if (!is_null($json_post) && empty($_POST)) {
            $_POST = $json_post;
        }

        return parent::createFromGlobals();
    }
}