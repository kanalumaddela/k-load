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

namespace K_Load\Routing;

use function is_array;

class Route
{
    public $name;

    public $prefix = '';

    public $uri = '';

    public $handler;

    public $middleware = [];

    public $parsedUri;

    public function __construct($uri, callable $handler, array $options = [])
    {
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    public function isStatic()
    {
        return false;
    }

    public function middleware($middleware)
    {
        if (is_array($middleware)) {
            foreach ($middleware as $item) {
                return $this->middleware($middleware);
            }
        } else {
            if (!isset($this->middleware[$middleware])) {
                $this->middleware[$middleware] = '';
            }
        }


        return $this;
    }
}