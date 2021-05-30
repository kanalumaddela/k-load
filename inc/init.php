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

use KLoad\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use function microtime;
use function print_r;

$whoops = new Run();
$whoops->pushHandler(new PrettyPageHandler());
$whoops->register();

App::setRoot(dirname(__DIR__));

App::init();

ob_start();

($res = App::dispatch())->send();

if (DEBUG && !$res instanceof JsonResponse) {
    if ($res instanceof JsonResponse) {
        $data = $res->getContent();
        dd($data);
    }

    echo "\n".'<!--'."\n\n";

    echo 'Script Time: '.(microtime(true) - APP_START).'s';

    echo "\n\n\nDB Query Log:\n\n";

    print_r(DB::connection()->getQueryLog());

    echo "\n".'-->';
}

if (ob_get_length() > 0) {
    ob_end_flush();
}

exit();
