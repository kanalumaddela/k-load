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

namespace K_Load\Controllers;

use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\User;
use K_Load\Util;
use Steam;
use function is_array;

class Dashboard extends BaseController
{
    public static $templateFolder = 'dashboard';

    public function index()
    {
        return self::view('index');
    }

    public function settings()
    {
        $post = $this->http->request;

        if ($post->has('save')) {
            $updated = User::update($_SESSION['steamid'], $post->all());
            $alert = $updated ? 'Background settings have been saved' : 'Failed to save, please try again and check the data/logs if necessary';

            if ($updated) {
                Cache::remove('loading-screen-'.$_SESSION['steamid']);
            }

            Util::flash('alert', $alert);
            Util::redirect('/dashboard/settings');
        }

        $data = [
            'user' => [
                'css' => User::get($_SESSION['steamid'], 'custom_css'),
            ],
        ];

        if (is_array($data['user']['css'])) {
            $data['user']['css'] = null;
        }

        return self::view('settings', $data);
    }

    public function users()
    {
        if (isset($_SESSION['steamid']) && count($_POST) > 0) {
            if ($_POST['type'] == 'perms') {
                $success = User::updatePerms($_POST['player'], $_POST);
                $data['alert'] = $success ? 'User\'s perms have been updated' : 'Failed to update perms';
            } else {
                User::action($_POST['player'], $_POST);
            }
        }

        if (isset($_GET['search'])) {
            $page = (isset($_GET['pg']) ? abs((int) $_GET['pg']) : 1);
            $data = User::search($_GET['search'], $page);
        } else {
            $data['total'] = User::total();
            $data['pages'] = ceil($data['total'] / USERS_PER_PAGE);
            $data['page'] = (isset($_GET['pg']) ? abs((int) $_GET['pg']) : 1);
            $users = User::all(($data['page'] <= $data['pages']) ? $data['page'] : $data['pages']);
            $data['users'] = isset($users['steamid']) ? [$users] : $users;
            if ($data['page'] > $data['pages']) {
                $data['page'] = $data['pages'];
            }
        }
        $data['permissions'] = User::getPerms(true);

        $steamids = implode(',', array_column($data['users'], 'steamid'));

        if (ENABLE_CACHE) {
            $cacheKey = 'pg-'.($data['page'] ?? ($page ?? 1)).'-'.($steamids);

            $steamInfo = Cache::remember($cacheKey, 3600, function () use ($steamids) {
                $info = Steam::Info($steamids);

                return $info['response']['players'] ?? null;
            });

            if (is_null($steamInfo)) {
                $steamInfo = [];
            }
        } else {
            $steamInfo = Steam::Info($steamids);
            $steamInfo = $steamInfo['response']['players'] ?? [];
        }

        foreach ($steamInfo as $index => $player) {
            if (!isset($player['steamid'])) {
                unset($steamInfo[$index]);
                continue;
            }

            $steamInfo[$player['steamid']] = $player;
            unset($steamInfo[$index]);
        }

        $data['steamInfo'] = $steamInfo;

        return self::view('users', $data);
    }

    public function user($steamid)
    {
        if (isset($_SESSION['steamid']) && count($_POST) > 0) {
            User::action($_POST['player'], $_POST);
        }

        $data['player'] = User::get($steamid);

        if ($data['player'] !== false && count($data['player']) > 0) {
            $data['player']['settings'] = json_decode($data['player']['settings'], true);

            return self::view('profile', $data);
        } else {
            Util::redirect('/dashboard/users');
        }
    }
}