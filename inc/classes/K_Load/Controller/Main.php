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

namespace K_Load\Controller;

use Exception;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\Template;
use K_Load\User;
use K_Load\Util;
use Steam;
use const ALLOW_THEME_OVERRIDE;
use const APP_ROOT;
use const ENABLE_CACHE;
use const ENABLE_REGISTRATION;
use const IGNORE_PLAYER_CUSTOMIZATIONS;
use function array_diff;
use function array_merge;
use function basename;
use function is_array;
use function is_file;
use function is_null;
use function json_decode;
use function json_encode;
use function method_exists;
use function scandir;
use function str_replace;

class Main
{
    public static function index()
    {
        global $config;

        $steamid = $_GET['sid'] ?? $_GET['steamid'] ?? null;
        if ($steamid === '%s') {
            $steamid = null;
        }

        $map = $_GET['map'] ?? $_GET['mapname'] ?? null;
        if ($map === '%m') {
            $map = null;
        }

        $data = ENABLE_CACHE ? Cache::remember('loading-screen'.(!is_null($steamid) ? '-'.$steamid : ''), 3600, function () use ($steamid, $map) {
            return self::getData($steamid, $map);
        }) : self::getData($steamid, $map);

        // override global setings with user specific settings
        if (isset($data['user']['settings']) && ENABLE_REGISTRATION && !IGNORE_PLAYER_CUSTOMIZATIONS) {
            $data['user']['settings'] = json_decode($data['user']['settings'], true);
            if (empty($data['user']['settings']['youtube']['list'])) {
                unset($data['user']['settings']['youtube']['list']);
            }
            $data['settings']['youtube'] = json_encode(array_merge(json_decode($data['settings']['youtube'], true), $data['user']['settings']['youtube']));
            unset($data['user']['settings']['youtube']['list']);

            $data['settings']['music'] = json_encode(array_merge(json_decode($data['settings']['music'], true), $data['user']['settings']['youtube']));
            $data['settings']['backgrounds'] = json_encode(array_merge(json_decode($data['settings']['backgrounds'], true), $data['user']['settings']['backgrounds']));

            unset($data['user']['settings']['youtube']);
            unset($data['user']['settings']['backgrounds']);
        }

        $theme = $config['loading_theme'] ?? 'default';
        if (!is_null($steamid)) {
            $theme = $data['user']['settings']['theme'] ?? $theme;
        }

        if (isset($_GET['theme']) && (!isset($data['user']['settings']['theme']) || ALLOW_THEME_OVERRIDE || IGNORE_PLAYER_CUSTOMIZATIONS)) {
            $theme = $_GET['theme'];
        }

        if (!Template::isLoadingTheme($theme)) {
            $theme = $config['loading_theme'];
        }

        if ($theme !== 'default') {
            Template::theme($theme);
        }

        Template::render('loading.twig', $data);
    }

    protected static function getData($steamid, $map)
    {
        $data = [
            'map'         => $map,
            'backgrounds' => Util::getBackgrounds(),
            'settings'    => Util::getSettings(),
            'css_exists'  => file_exists(APP_ROOT.'/data/users/'.$steamid.'.css'),
        ];

        $user = User::get($steamid, 'name', 'steamid2', 'steamid3', 'settings', 'admin', 'banned', 'registered');
        if (empty($user)) {
            $user = Steam::Convert($steamid);
        }

        $data['user'] = $user;

        if (empty($data['settings'])) {
            throw new Exception('Failed to get settings, check the logs in data/logs/mysql');
        }

        $steamInfo = null;

        if (!empty($steamid)) {
            $steamInfo = ENABLE_CACHE ? Cache::remember('loading-steam-api-'.$steamid, 3600, function () use ($steamid) {
                return Steam::User($steamid);
            }) : Steam::User($steamid);
        }

        if (is_array($steamInfo)) {
            $data['user'] = array_merge($data['user'], $steamInfo);
        }

        // shit addon loading
        $addonsRoot = APP_ROOT.'/inc/classes/mods/';
        $addons = array_diff(scandir($addonsRoot), ['.', '..']);

        if (count($addons) > 0) {
            foreach ($addons as $addon) {
                if (!is_file($addonsRoot.$addon)) {
                    continue;
                }

                $addon_name = basename($addon, '.class.php');

                require_once $addonsRoot.$addon;

                $addon_name_real = str_replace('addon_', '', $addon_name);
                if (ENABLE_CACHE) {
                    $data['custom'][$addon_name_real] = Cache::remember('custom-mod-'.$addon_name_real, 120, function () use ($steamid, $map, $addon_name) {
                        $addon_instance = new $addon_name($steamid, $map);

                        return method_exists($addon_instance, 'data') ? $addon_instance->data() : null;
                    });
                } else {
                    $addon_instance = new $addon_name($steamid, $map);
                    if (method_exists($addon_instance, 'data')) {
                        $data['custom'][$addon_name_real] = $addon_instance->data();
                    }
                }
            }
        }

        return $data;
    }

    public static function logout()
    {
        Steam::logout();
    }
}
