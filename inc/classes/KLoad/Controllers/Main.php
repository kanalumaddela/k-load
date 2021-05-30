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

use Exception;
use kanalumaddela\SteamLogin\SteamLogin;
use KLoad\Facades\Cache;
use KLoad\Facades\DB;
use KLoad\Helpers\Util;
use KLoad\Models\Setting;
use KLoad\Models\User;
use KLoad\View\LoadingView;
use function array_merge;
use function count;
use function file_exists;
use function is_null;
use function KLoad\loadingView;
use const KLoad\APP_ROOT;
use const KLoad\DEBUG;
use const KLoad\ENABLE_REGISTRATION;
use const KLoad\IGNORE_PLAYER_CUSTOMIZATIONS;

class Main extends BaseController
{
    public function error()
    {
        require_once __DIR__ . '/../../../error.php';

        kload_error_page();
    }

    public function index()
    {
        $data = static::buildBaseData();

        if (!empty($data['steamid'])) {
            $data['user'] = Cache::remember('user-' . $data['steamid'], 3600, function () use ($data) {
                $user = User::select('name', 'settings', DB::raw('IF(`custom_css` = \'\', null, `custom_css`) as `custom_css`'))->where('steamid', $data['steamid'])->first();

                $user = !is_null($user) ? $user->toArray() : [];

                try {
                    $steamInfo = (array) SteamLogin::userInfo($data['steamid']);
                } catch (Exception $e) {
                    $steamInfo = [];
                }

                $user = array_merge($user, $steamInfo);

                return !empty($user) ? $user : null;
            });
        }

        if (((DEBUG && !empty($data['user'])) || (ENABLE_REGISTRATION && !IGNORE_PLAYER_CUSTOMIZATIONS && !empty($data['user']))) && isset($user['settings'])) {
            $user = $data['user'];

            LoadingView::setTheme($user['settings']['theme']);

            $data['backgrounds'] = array_merge($data['backgrounds'], $user['settings']['backgrounds']);
            $data['youtube'] = array_merge($data['settings']['youtube'], $user['settings']['youtube']);
        }

        if (isset($data['user'])) {
            $data['player'] = &$data['user'];
        }

        return $this->view('loading', $data);
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
            'map'            => $map,
            'steamid'        => $steamid,
            'settings'       => Setting::whereIn('name', static::getLoadingScreenSettings())->get()->pluck('value', 'name'),
            'backgrounds'    => ['list' => Util::getBackgrounds()],
            'forcedGamemode' => $_GET['gamemode'] ?? null,
            'user'           => [],
        ];

        $data['backgrounds'] = array_merge($data['backgrounds'], $data['settings']['backgrounds']);
        $data['theme'] = file_exists(APP_ROOT.'/themes/'.LoadingView::getTheme().'/config.php') ? include_once APP_ROOT.'/themes/'.LoadingView::getTheme().'/config.php' : [];

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
