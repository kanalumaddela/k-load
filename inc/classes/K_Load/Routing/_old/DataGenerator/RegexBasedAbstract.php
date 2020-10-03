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

namespace K_Load\Routing\DataGenerator;

use FastRoute\BadRouteException;
use FastRoute\DataGenerator;
use K_Load\Routing\Route;
use function array_chunk;
use function array_map;
use function ceil;
use function count;
use function is_string;
use function max;
use function preg_match;
use function preg_quote;
use function round;
use function sprintf;
use function strpos;

abstract class RegexBasedAbstract implements DataGenerator
{
    /** @var mixed[][] */
    protected $staticRoutes = [];

    /** @var Route[][] */
    protected $methodToRegexToRoutesMap = [];

    public function addRoute($httpMethod, $routeData, $handler)
    {
        if ($this->isStaticRoute($routeData)) {
            return $this->addStaticRoute($httpMethod, $routeData, $handler);
        } else {
            return $this->addVariableRoute($httpMethod, $routeData, $handler);
        }
    }

    /**
     * @param mixed[]
     *
     * @return bool
     */
    protected function isStaticRoute($routeData)
    {
        return count($routeData) === 1 && is_string($routeData[0]);
    }

    protected function addStaticRoute($httpMethod, $routeData, $handler)
    {
        $routeStr = $routeData[0];

        if (isset($this->staticRoutes[$httpMethod][$routeStr])) {
            throw new BadRouteException(sprintf(
                'Cannot register two routes matching "%s" for method "%s"',
                $routeStr,
                $httpMethod
            ));
        }

        if (isset($this->methodToRegexToRoutesMap[$httpMethod])) {
            foreach ($this->methodToRegexToRoutesMap[$httpMethod] as $route) {
                if ($route->matches($routeStr)) {
                    throw new BadRouteException(sprintf(
                        'Static route "%s" is shadowed by previously defined variable route "%s" for method "%s"',
                        $routeStr,
                        $route->regex,
                        $httpMethod
                    ));
                }
            }
        }

        $this->staticRoutes[$httpMethod][$routeStr] = $handler;
    }

    protected function addVariableRoute($httpMethod, $routeData, $handler)
    {
        [$regex, $variables] = $this->buildRegexForRoute($routeData);

        if (isset($this->methodToRegexToRoutesMap[$httpMethod][$regex])) {
            throw new BadRouteException(sprintf(
                'Cannot register two routes matching "%s" for method "%s"',
                $regex,
                $httpMethod
            ));
        }

        $route = new Route($httpMethod, $handler, $regex, $variables);

        $this->methodToRegexToRoutesMap[$httpMethod][$regex] = $route;

        return $route;
    }

    /**
     * @param mixed[]
     *
     * @return mixed[]
     */
    protected function buildRegexForRoute($routeData)
    {
        $regex = '';
        $variables = [];
        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }

            [$varName, $regexPart] = $part;

            if (isset($variables[$varName])) {
                throw new BadRouteException(sprintf(
                    'Cannot use the same placeholder "%s" twice',
                    $varName
                ));
            }

            if ($this->regexHasCapturingGroups($regexPart)) {
                throw new BadRouteException(sprintf(
                    'Regex "%s" for parameter "%s" contains a capturing group',
                    $regexPart,
                    $varName
                ));
            }

            $variables[$varName] = $varName;
            $regex .= '('.$regexPart.')';
        }

        return [$regex, $variables];
    }

    /**
     * @param string
     *
     * @return bool
     */
    protected function regexHasCapturingGroups($regex)
    {
        if (false === strpos($regex, '(')) {
            // Needs to have at least a ( to contain a capturing group
            return false;
        }

        // Semi-accurate detection for capturing groups
        return (bool) preg_match(
            '~
                (?:
                    \(\?\(
                  | \[ [^\]\\\\]* (?: \\\\ . [^\]\\\\]* )* \]
                  | \\\\ .
                ) (*SKIP)(*FAIL) |
                \(
                (?!
                    \? (?! <(?![!=]) | P< | \' )
                  | \*
                )
            ~x',
            $regex
        );
    }

    /**
     * @return mixed[]
     */
    public function getData()
    {
        if (empty($this->methodToRegexToRoutesMap)) {
            return [$this->staticRoutes, []];
        }

        return [$this->staticRoutes, $this->generateVariableRouteData()];
    }

    /**
     * @return mixed[]
     */
    protected function generateVariableRouteData()
    {
        $data = [];
        foreach ($this->methodToRegexToRoutesMap as $method => $regexToRoutesMap) {
            $chunkSize = $this->computeChunkSize(count($regexToRoutesMap));
            $chunks = array_chunk($regexToRoutesMap, $chunkSize, true);
            $data[$method] = array_map([$this, 'processChunk'], $chunks);
        }

        return $data;
    }

    /**
     * @param int
     *
     * @return int
     */
    protected function computeChunkSize($count)
    {
        $numParts = max(1, round($count / $this->getApproxChunkSize()));

        return (int) ceil($count / $numParts);
    }

    /**
     * @return int
     */
    abstract protected function getApproxChunkSize();

    /**
     * @return mixed[]
     */
    abstract protected function processChunk($regexToRoutesMap);
}
