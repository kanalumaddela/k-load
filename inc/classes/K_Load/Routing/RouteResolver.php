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

use Closure;
use Illuminate\Database\Eloquent\Model;
use K_Load\App;
use ReflectionFunction;
use ReflectionMethod;
use function array_values;
use function count;
use function is_array;
use function is_string;

class RouteResolver
{
    public function resolve($handler, $vars = [])
    {
        if ($handler instanceof Closure) {
            $reflection = new ReflectionFunction($handler);

            $vars = static::parseReflection($reflection, $vars);

            return [$handler, $vars];
        }

        if (is_array($handler) && is_string($handler[0])) {
            $handler[0] = App::get($handler[0]);

            $reflection = new ReflectionMethod($handler[0], $handler[1]);

            $vars = static::parseReflection($reflection, $vars);

            return [$handler, $vars];
        }

        if (is_string($handler) && empty($vars)) {
            return $handler;
        }

        return [$handler, $vars];
    }

    /**
     * @param ReflectionFunction|ReflectionMethod $reflection
     * @param array                               $routeVars
     *
     * @return array
     */
    protected static function parseReflection($reflection, array $routeVars = [])
    {
        $args = [];
//        $routeKeys = array_keys($routeVars);
        $routeVals = array_values($routeVars);

        $attemptRouteBinding = count($routeVars) !== 0;

//        $parameterCount = count($parameters = $reflection->getParameters());

        $i = 0;

        foreach ($reflection->getParameters() as $reflectionParameter) {
            if (isset($routeVals[$i])) {
                $args[$i] = $routeVals[$i];
            }

            if (!empty($class = $reflectionParameter->getClass())) {
                if ($attemptRouteBinding && $class->isSubclassOf(Model::class)) {
                    /**
                     * @var Model $model
                     */
                    $class = $class->getName();
                    $model = new $class();

                    $args[$i] = $model;

                    if (isset($routeVals[$i])) {
                        $args[$i] = $model->resolveRouteBinding($routeVals[$i]);
                    }
                } elseif (!isset($routeVals[$i])) {
                    $args[$i] = App::get($class->getName());
                }
            }

            $i++;
        }

        return $args;
    }
}
