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
use K_Load\Lang;
use K_Load\Template;
use Symfony\Component\HttpFoundation\Response;

/**
 * @param       $template
 * @param array $data
 * @param int   $httpCode
 *
 * @return \Symfony\Component\HttpFoundation\Response
 */
function view($template, array $data = [], $httpCode = 200)
{
    $template = str_replace('.twig', '', $template);

    $response = new Response(Template::render($template.'.twig', $data));
    $response->setCharset('UTF-8');
    $response->headers->set('Content-Type', 'text/html');
    $response->setStatusCode($httpCode);

    return $response;
}

/**
 * @return string
 */
function lang()
{
    return call_user_func_array([Lang::class, 'get'], func_get_args());
}

/**
 * @return bool
 */
function loggedIn()
{
    $loggedIn = isset($_SESSION['id'], $_SESSION['steamid']);

    if (!$loggedIn) {
        session_unset();
    }

    return $loggedIn;
}

function displayLoginPageIfGuest()
{
    if (!loggedIn()) {
        view('login')->send();
        die();
    }
}
