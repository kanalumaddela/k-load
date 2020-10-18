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

use function md5;
use function method_exists;

class Route extends \FastRoute\Route
{
    /**
     * @var \FastRoute\RouteCollector
     */
    protected static $routeCollector;

    public $name = '';

    public static function bindInstance(\FastRoute\RouteCollector $routeCollector)
    {
        static::$routeCollector = $routeCollector;
    }

    public static function __callStatic($name, $arguments)
    {
        if (method_exists($name, static::$routeCollector)) {
        }
        // TODO: Implement __callStatic() method.
    }

    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        if (empty($this->name)) {
            $this->name = md5($this->httpMethod.'_'.$this->regex);
        }

        return $this->name;
    }
}
