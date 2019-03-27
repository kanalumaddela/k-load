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

        $steamid = $_GET['steamid'] ?? null;
        if ($steamid == '%s') {
            $steamid = null;
        }
        $map = $_GET['mapname'] ?? null;

        if (!empty($steamid) && !file_exists(APP_ROOT.'/data/users/'.$steamid.'.css')) {
            touch(APP_ROOT.'/data/users/'.$steamid.'.css');
        }

        if (ENABLE_CACHE) {
            if (!empty($steamid)) {
                $data['user'] = Cache::remember('player-'.$steamid, 0, function () use ($steamid) {
                    $steamids = Steam::Convert($steamid);
                    $data = User::get($steamid) + ($steamids ? (Steam::User($steamid) ?? []) : []) + ($steamids ?? []);
                    if (ENABLE_REGISTRATION && isset($data['settings'])) {
                        $data['settings'] = json_decode($data['settings'], true);
                        $data['settings']['backgrounds'] = json_encode($data['settings']['backgrounds']);
                        $data['settings']['youtube'] = json_encode($data['settings']['youtube']);
                    }

                    return count($data) > 0 ? $data : null;
                });
            }

            $data['backgrounds'] = Cache::remember('backgrounds', 60, [Util::class, 'getBackgrounds']);
            $data['settings'] = Cache::remember('settings', 0, function () {
                return Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages', 'music');
            });
        } else {
            if (!empty($steamid)) {
                $steamids = Steam::Convert($steamid);
                $data['user'] = (ENABLE_REGISTRATION ? User::get($steamid) : []) + ($steamids ? (Steam::User($steamid) ?? []) : []) + ($steamids ?? []);
                if (isset($data['user']['settings'])) {
                    $data['user']['settings'] = json_decode($data['user']['settings'], true);
                    $data['user']['settings']['backgrounds'] = json_encode($data['user']['settings']['backgrounds']);
                    $data['user']['settings']['youtube'] = json_encode($data['user']['settings']['youtube']);
                }
            }

            $data['settings'] = Util::getSetting('backgrounds', 'community_name', 'description', 'youtube', 'rules', 'staff', 'messages', 'music');

            $data['backgrounds'] = Util::getBackgrounds();
        }

        if (isset($data['user']['settings']) && !ENABLE_REGISTRATION) {
            unset($data['user']['settings']);
        }

        // dumb fix for users who enabled youtube, but want to listen to global music set by owner
        if (isset($data['user']['settings'])) {
            $tmp_user_settings = json_decode($data['user']['settings']['youtube'], true);
            $tmp_settings = json_decode($data['settings']['youtube'], true);

            $tmp_settings_music = json_decode($data['settings']['music'], true);

            $tmp_settings_music['enable'] = $tmp_user_settings['enable'] ?? 1;
            $tmp_settings_music['random'] = $tmp_user_settings['random'] ?? 0;
            $tmp_settings_music['volume'] = $tmp_user_settings['volume'] ?? 15;

            $data['settings']['music'] = json_encode($tmp_settings_music);

            if ($tmp_user_settings['enable'] == 1 && count($tmp_user_settings['list']) <= 0) {
                $tmp_user_settings['list'] = $tmp_settings['list'];
                $data['user']['settings']['youtube'] = json_encode($tmp_user_settings);
            }
        }

        $data['map'] = $map;
        $theme = $config['loading_theme'] ?? 'default';
        $theme = (THEME_OVERRIDE ? ($_GET['theme'] ?? ($data['user']['settings']['theme'] ?? $theme)) : ($data['user']['settings']['theme'] ?? ($_GET['theme'] ?? $theme)));

        if (Template::isLoadingTheme($theme)) {
            Template::theme($theme);
            Template::init();
        }

        $addons = array_filter(glob(APP_ROOT.'/inc/classes/mods/addon_*.class.php'), 'is_file');
        if (count($addons) > 0) {
            foreach ($addons as $addon) {
                $addon_name = basename($addon, '.class.php');
                $addon_name_real = str_replace('addon_', '', $addon_name);
                if (ENABLE_CACHE) {
                    $data['custom'][$addon_name_real] = Cache::remember('custom-'.$addon_name_real, 120, function () use ($steamid, $map, $addon_name) {
                        $addon_instance = new $addon_name($steamid, $map);
                        if (method_exists($addon_instance, 'data')) {
                            return $addon_instance->data();
                        } else {
                            return;
                        }
                    });
                } else {
                    $addon_instance = new $addon_name($steamid, $map);
                    if (method_exists($addon_instance, 'data')) {
                        $data['custom'][$addon_name_real] = $addon_instance->data();
                    }
                }
            }
        }

        Template::render('loading.twig', $data);
    }

    public static function logout()
    {
        Steam::logout();
    }
}
