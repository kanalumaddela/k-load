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

namespace KLoad\Controllers;

use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use KLoad\User;
use KLoad\Util;
use Steam;
use Symfony\Component\HttpFoundation\JsonResponse;
use function count;
use function is_null;
use function md5;
use function simplexml_load_string;
use function strpos;
use const ENABLE_CACHE;
use const JSON_PRETTY_PRINT;

class API extends BaseController
{
    public static function player($steamid, $info = null)
    {
        if (isset($_SESSION['steamid'])) {
            $session_steamid = $_SESSION['steamid'];
            unset($_SESSION['steamid']);
        }

        $data = (ENABLE_CACHE ? Cache::remember('api-player-'.$steamid, 120, function () use ($steamid) {
            $data = User::get($steamid) + (Steam::User($steamid) ?? []);

            return $data;
        }) : User::get($steamid) + (Steam::User($steamid) ?? []));

        if (isset($session_steamid)) {
            $_SESSION['steamid'] = $session_steamid;
        }

        $data['success'] = count($data) > 0;
        Util::to_top($data, 'success');
        if (isset($data[$info])) {
            $data = $data[$info];
            if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] == 'raw' && (strpos($info, 'avatar') !== false || strpos($info, 'profileurl') !== false)) {
                Util::redirect($data);
            }
        }

        $encoding = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        if (isset($_GET['formatted'])) {
            $encoding = $encoding | JSON_PRETTY_PRINT;
        }

        return (new JsonResponse($data))->setEncodingOptions($encoding);
    }

    public static function players($steamids)
    {
        $hash = md5($steamids);

        $data = [
            'success' => false,
        ];

        if (ENABLE_CACHE) {
            $data['data'] = Cache::remember('steam-api-players-'.$hash, 3600, function () use ($steamids) {
                return Steam::Users($steamids);
            });
        } else {
            $data['data'] = Steam::Users($steamids);
        }

        $data['success'] = !is_null($data['data']);

        return new JsonResponse($data);
    }

    public static function group($name)
    {
        $data = (ENABLE_CACHE ? Cache::remember('api-group-'.$name, 60, function () use ($name) {
            return Steam::Group($name)->asXML();
        }) : Steam::Group($name)->asXML());

        $data = simplexml_load_string($data);
        $data->success = isset($data);

        $encoding = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        if (isset($_GET['formatted'])) {
            $encoding = $encoding | JSON_PRETTY_PRINT;
        }

        return (new JsonResponse($data))->setEncodingOptions($encoding);
    }
}
