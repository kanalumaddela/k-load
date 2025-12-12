<?php

/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2025 kanalumaddela
 * @license   MIT
 */

namespace KLoad;

use JsonException;
use function file_get_contents;
use function is_null;
use function json_decode;

class Request extends \Symfony\Component\HttpFoundation\Request
{
    public static function createFromGlobals(): static
    {
        try {
            $json_post = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

            if (!is_null($json_post) && empty($_POST)) {
                $_POST = $json_post;
            }
        } catch (JsonException $e) {
        }

        return parent::createFromGlobals();
    }
}
