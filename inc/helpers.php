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

use KLoad\Exceptions\HttpException;
use KLoad\Http\RedirectResponse;
use KLoad\View\LoadingView;
use KLoad\View\View;
use Symfony\Component\HttpFoundation\Response;

//init(['dsn' => 'https://0bdc6629de78435f807c56358e3cdbae@o259687.ingest.sentry.io/1455550']);

/**
 * Is the current request https?
 *
 * @return bool
 */
function is_https()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' : false);
}

/**
 * Return defined variables in a file.
 *
 * @param $file
 *
 * @return array
 */
function get_vars($file)
{
    require_once $file;

    return \get_defined_vars();
}

// stackoverflow gang
// short array syntax instead of array()
function var_export_fixed($var, $indent = ''): ?string
{
    switch (\gettype($var)) {
        case 'string':
            return '\''.\addcslashes($var, "\\\$\"\r\n\t\v\f").'\'';
        case 'array':
            $indexed = \array_keys($var) === \range(0, \count($var) - 1);
            $r = [];
            foreach ($var as $key => $value) {
                $r[] = "$indent    "
                    .($indexed ? '' : var_export_fixed($key).' => ')
                    .var_export_fixed($value, "$indent    ");
            }

            return "[\n".\implode(",\n", $r)."\n".$indent.']';
        case 'boolean':
            return $var ? 'true' : 'false';
        default:
            return \var_export($var, true);
    }
}

/**
 * @param       $template
 * @param array $data
 * @param int   $httpCode
 *
 * @return Response
 */
function view($template, array $data = [], int $httpCode = 200): Response
{
    $response = new Response(View::render($template, $data));
    $response->setCharset('UTF-8');
    $response->headers->set('Content-Type', 'text/html');
    $response->setStatusCode($httpCode);

    return $response;
}

function loadingView(array $data = [])
{
    $response = new Response(LoadingView::render('loading', $data));
    $response->setCharset('UTF-8');
    $response->headers->set('Content-Type', 'text/html');
    $response->setStatusCode(200);

    return $response;
}

/**
 * @return string
 *
 * @see  Lang::get
 */
function lang(): string
{
    return \call_user_func_array([Lang::class, 'get'], \func_get_args());
}

/**
 * @return bool
 */
function loggedIn()
{
    $loggedIn = isset($_SESSION['kload'], $_SESSION['kload']['user'], $_SESSION['kload']['user']['id']);

    if (!$loggedIn) {
        \session_unset();
    }

    return $loggedIn;
}

function directoryTree($dir): array
{
    $arr = [];
    $name = \basename($dir);
    $arr[$name] = [];

    $folders = $files = [];

    foreach (\scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $item = $dir.DIRECTORY_SEPARATOR.$item;

        if (\is_dir($item)) {
            $folders[\basename($item)] = directoryTree($item);
        } else {
            $files[\basename($item)] = $item;
        }
    }

    \ksort($folders);
    \ksort($files);

    $arr = \compact('folders', 'files');

    return $arr;
}

function db()
{
    global $capsule;

    return $capsule::connection();
}

function displayLoginPageIfGuest()
{
    if (!\KLoad\Facades\Session::user()) {
        return view('login');
    }
}

function isAdminUser()
{
//    throw new HttpException(403);

    if (\KLoad\Facades\Session::user()['admin'] !== true) {
        return new HttpException(403);
    }
}

function redirect($url, $status = 302, array $headers = []): RedirectResponse
{
    return new RedirectResponse($url, $status, $headers);
}

function flash($key, $data): void
{
    \KLoad\Facades\Session::flash($key, $data);
}
