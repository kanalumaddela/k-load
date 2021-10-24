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

use KLoad\Helpers\Util;
use KLoad\Models\Setting;
use KLoad\Models\User;
use KLoad\View\LoadingView;
use KLoad\View\View;
use function array_merge;
use function count;
use function file_exists;
use function KLoad\loadingView;
use const KLoad\ALLOW_THEME_OVERRIDE;
use const KLoad\APP_ROOT;
use const KLoad\DEBUG;
use const KLoad\ENABLE_REGISTRATION;
use const KLoad\IGNORE_PLAYER_CUSTOMIZATIONS;

class Main extends BaseController
{
    public function index()
    {

        $data = static::buildBaseData();

        dump($data);

        $theme = LoadingView::getTheme();

        if (ENABLE_REGISTRATION && !IGNORE_PLAYER_CUSTOMIZATIONS && !empty($data['steamid'])) {
            $player = User::select('name', 'steamid', 'steamid2', 'steamid3', 'admin', 'settings', 'banned', 'registered')->where('steamid', $data['steamid'])->first();

            if ($player) {
                $player = $player->toArray();

                $data['user'] = $player;
                $data['settings'] = array_merge($data['settings'], $player['settings']);
                unset($data['settings']['theme']);

                if (!IGNORE_PLAYER_CUSTOMIZATIONS) {
                    $theme = $player['settings']['theme'];
                }
            }
        }

        dd($data['user']);

        if (!empty($_GET['theme']) && ALLOW_THEME_OVERRIDE) {
            $theme = $_GET['theme'];
        }

        dd($data);

//        dd($theme);

        View::setTheme('new');
//        LoadingView::setTheme($theme);

        return \KLoad\view('loading', ['data' => json_encode($data)]);

//        return $this->view('loading', $data);
    }

    protected function buildBaseData(): array
    {
        $steamid = $_GET['sid'] ?? $_GET['steamid'] ?? null;
        if ($steamid === '%s') {
            $steamid = null;
        }

        $map = $_GET['map'] ?? $_GET['mapname'] ?? null;
        if ($map === '%m') {
            $map = null;
        }

        $data = [
            'map' => $map,
            'steamid' => $steamid,
            'settings' => Setting::whereIn('name', static::getLoadingScreenSettings())->get()->pluck('value', 'name')->toArray(),
            'forcedGamemode' => $_GET['gamemode'] ?? null,
            'user' => [],
        ];

        $data['settings']['backgrounds'] = array_merge(['list' => Util::getBackgrounds()], $data['settings']['backgrounds']);
        $data['theme'] = file_exists(APP_ROOT . '/themes/' . LoadingView::getTheme() . '/config.php') ? include APP_ROOT . '/themes/' . LoadingView::getTheme() . '/config.php' : [];

        return $data;
    }

    protected static function getLoadingScreenSettings(): array
    {
        return [
            'backgrounds',
            'community_name',
            'description',
            'logo',
            'messages',
            'music',
            'rules',
            'staff',
            'youtube',
        ];
    }

    public function view($template, array $data = [])
    {
        if (count(static::$dataHooks) > 0) {
            $data = static::addHookData($data);
        }

        return loadingView($template, $data);
    }
}
