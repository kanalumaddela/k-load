<?php

namespace K_Load\Controller;

use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use K_Load\Template;
use K_Load\User;
use K_Load\Util;

class Users
{
    public static function all()
    {
        if (isset($_SESSION['steamid']) && \count($_POST) > 0) {
            if ($_POST['type'] == 'perms') {
                $success = User::updatePerms($_POST['player'], $_POST);
                $data['alert'] = $success ? 'User\'s perms have been updated' : 'Failed to update perms';
            } else {
                User::action($_POST['player'], $_POST);
            }
        }

        if (isset($_GET['search'])) {
            $page = (isset($_GET['pg']) ? $_GET['pg'] : 1);
            $data = User::search($_GET['search'], $page);
        } else {
            $data['total'] = User::total();
            $data['pages'] = \ceil($data['total'] / USERS_PER_PAGE);
            $data['page'] = (isset($_GET['pg']) ? $_GET['pg'] : 1);
            $users = User::all(($data['page'] <= $data['pages']) ? $data['page'] : 1);
            $data['users'] = isset($users['steamid']) ? [$users] : $users;
            if ($data['page'] > $data['pages']) {
                $data['page'] = $data['pages'];
            }
        }
        $data['permissions'] = User::getPerms(true);

        $steamids = \implode(',', \array_column($data['users'], 'steamid'));

        if (ENABLE_CACHE) {
            $cacheKey = 'pg-'.($data['page'] ?? ($page ?? 1)).'-'.($steamids);

            $steamInfo = Cache::remember($cacheKey, 3600, function () use ($steamids) {
                $info = \Steam::Info($steamids);

                return $info['response']['players'] ?? null;
            });

            if (\is_null($steamInfo)) {
                $steamInfo = [];
            }
        } else {
            $steamInfo = \Steam::Info($steamids);
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

        Template::render('@dashboard/users.twig', $data);
    }

    public static function get($steamid)
    {
        if (isset($_SESSION['steamid']) && \count($_POST) > 0) {
            User::action($_POST['player'], $_POST);
        }

        $data['player'] = User::get($steamid);

        if ($data['player'] !== false && \count($data['player']) > 0) {
            $data['player']['settings'] = \json_decode($data['player']['settings'], true);
            Template::render('@dashboard/profile.twig', $data);
        } else {
            Util::redirect('/dashboard/users');
        }
    }
}
