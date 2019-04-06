<?php

namespace K_Load\Controller;

use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\Template;
use K_Load\User;
use K_Load\Util;
use Steam;

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

        $data = \ENABLE_CACHE ? Cache::remember('loading-screen'.(!\is_null($steamid) ? '-'.$steamid : ''), 3600, function () use ($steamid, $map) {
            return self::getData($steamid, $map);
        }) : self::getData($steamid, $map);

        $theme = THEME_OVERRIDE && isset($_GET['theme']) ? $_GET['theme'] : (!IGNORE_PLAYER_CUSTOMIZATIONS && isset($data['user']['settings']['theme']) ? $data['user']['settings']['theme'] : $config['loading_theme']);
        if (!Template::isLoadingTheme($theme)) {
            $theme = $config['loading_theme'];
        }

        if ($theme !== 'default') {
            Template::theme($theme);
            Template::init();
        }

        Template::render('loading.twig', $data);
    }

    public static function logout()
    {
        Steam::logout();
    }

    protected static function getData($steamid, $map)
    {
        $settings = [
            'backgrounds',
            'community_name',
            'description',
            'youtube',
            'rules',
            'staff',
            'messages',
            'music',
        ];

        $data = [
            'map'         => $map,
            'backgrounds' => Util::getBackgrounds(),
            'settings'    => Util::getSetting(...$settings),
            'user'        => \array_merge(User::get($steamid, 'name', 'steamid2', 'steamid3', (\ENABLE_REGISTRATION && !\IGNORE_PLAYER_CUSTOMIZATIONS) ? 'settings' : '', 'admin', 'banned', 'registered'), Steam::Convert($steamid)),
            'css_exists'  => file_exists(APP_ROOT.'/data/users/'.$steamid.'.css'),
        ];

        if (empty($data['settings'])) {
            throw new \Exception('Failed to get settings, check the logs in data/logs/mysql');
        }

        $steamInfo = null;

        if (!empty($steamid)) {
            $steamInfo = \ENABLE_CACHE ? Cache::remember('loading-steam-api-'.$steamid, 3600, function () use ($steamid) {
                return Steam::User($steamid);
            }) : Steam::User($steamid);
        }

        if (\is_array($steamInfo)) {
            $data['user'] = \array_merge($data['user'], $steamInfo);
        }

        // override global setings with user specific settings
        if (isset($data['user']['settings'])) {
            $data['user']['settings'] = \json_decode($data['user']['settings'], true);
            $data['settings']['youtube'] = \json_encode($data['user']['settings']['youtube']);
            unset($data['user']['settings']['youtube']['list']);
            $data['settings']['music'] = \json_encode(\array_merge(\json_decode($data['settings']['music'], true), $data['user']['settings']['youtube']));
            $data['settings']['backgrounds'] = \json_encode(\array_merge(\json_decode($data['settings']['backgrounds'], true), $data['user']['settings']['backgrounds']));

            unset($data['user']['settings']['youtube']);
            unset($data['user']['settings']['backgrounds']);
        }

        // shit addon loading
        $addonsRoot = \APP_ROOT.'/inc/classes/mods/';
        $addons = \array_diff(\scandir($addonsRoot), ['.', '..']);

        if (count($addons) > 0) {
            foreach ($addons as $addon) {
                if (!\is_file($addonsRoot.$addon)) {
                    continue;
                }

                $addon_name = \basename($addon, '.class.php');

                require_once $addonsRoot.$addon;

                $addon_name_real = \str_replace('addon_', '', $addon_name);
                if (ENABLE_CACHE) {
                    $data['custom'][$addon_name_real] = Cache::remember('custom-'.$addon_name_real, 120, function () use ($steamid, $map, $addon_name) {
                        $addon_instance = new $addon_name($steamid, $map);

                        return \method_exists($addon_instance, 'data') ? $addon_instance->data() : null;
                    });
                } else {
                    $addon_instance = new $addon_name($steamid, $map);
                    if (\method_exists($addon_instance, 'data')) {
                        $data['custom'][$addon_name_real] = $addon_instance->data();
                    }
                }
            }
        }

        return $data;
    }
}
