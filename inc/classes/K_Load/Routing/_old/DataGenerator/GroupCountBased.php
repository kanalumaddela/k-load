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

use function count;
use function implode;
use function max;
use function str_repeat;

class GroupCountBased extends RegexBasedAbstract
{
    protected function getApproxChunkSize()
    {
        return 10;
    }

    protected function processChunk($regexToRoutesMap)
    {
        $routeMap = [];
        $regexes = [];
        $numGroups = 0;
        foreach ($regexToRoutesMap as $regex => $route) {
            $numVariables = count($route->variables);
            $numGroups = max($numGroups, $numVariables);

            $regexes[] = $regex.str_repeat('()', $numGroups - $numVariables);
            $routeMap[$numGroups + 1] = [$route->handler, $route->variables];

            ++$numGroups;
        }

        $regex = '~^(?|'.implode('|', $regexes).')$~';

        return ['regex' => $regex, 'routeMap' => $routeMap];
    }
}