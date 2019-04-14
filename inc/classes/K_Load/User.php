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

namespace K_Load;

use Database;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use Steam;
use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_replace_recursive;
use function array_values;
use function ceil;
use function count;
use function file_exists;
use function file_put_contents;
use function filter_var;
use function implode;
use function in_array;
use function json_decode;
use function json_encode;
use function sort;
use function unlink;

class User
{
    public static function all($page = 1)
    {
        $page = $page - 1;

        return Database::conn()->select('SELECT `name`, `steamid`, `admin`, `perms`, `banned` FROM `kload_users`')->limit(USERS_PER_PAGE, $page * USERS_PER_PAGE)->execute();
    }

    public static function search($query, $page = 1)
    {
        $data['page'] = $page - 1;
        $data['total'] = Database::conn()->count('kload_users')->where("`name` LIKE '%?%' OR `steamid` LIKE '%?%' OR `steamid2` LIKE '%?%' OR `steamid3` LIKE '%?%'", [$query, $query, $query, $query])->execute();
        $data['pages'] = ceil($data['total'] / USERS_PER_PAGE);
        if ($data['page'] > $data['total']) {
            $data['page'] = $data['total'];
        }
        $users = Database::conn()->select('SELECT `name`, `steamid`, `admin`, `perms`, `banned` FROM `kload_users`')->where("`name` LIKE '%?%' OR `steamid` LIKE '%?%' OR `steamid2` LIKE '%?%' OR `steamid3` LIKE '%?%'", [$query, $query, $query, $query])->limit(USERS_PER_PAGE, $data['page'] * USERS_PER_PAGE)->execute();
        $data['users'] = ($data['total'] > 1 ? $users : [$users]);
        if ($data['page'] == 0) {
            $data['page'] = 1;
        }

        return $data;
    }

    public static function total()
    {
        return Database::conn()->count('kload_users')->execute();
    }

    /**
     * @param       $steamid
     * @param mixed ...$columns
     *
     * @return array|string
     */
    public static function getInfo($steamid, ...$columns)
    {
        return Database::conn()->select('SELECT `'.(implode('`,`', $columns)).'` FROM `kload_users`')->where("`steamid` = '?'", [$steamid])->execute();
    }

    public static function action($steamid, $post)
    {
        $success = false;
        $message = 'Failed to complete action';
        if (isset($steamid) && isset($post['csrf'])) {
            switch ($post['type']) {
                case 'copy':
                    $success = self::copy($_SESSION['steamid'], $steamid, $post['csrf']);
                    $message = $success ? 'Your settings have been copied' : 'Failed to copy settings';
                    break;
                case 'ban':
                    $success = self::ban($steamid, $post['csrf']);
                    $message = $success ? 'User has been banned' : 'Failed to ban user';
                    break;
                case 'unban':
                    $success = self::unban($steamid, $post['csrf']);
                    $message = $success ? 'User has been unbanned' : 'Failed to unban user';
                    break;
                default:
                    $message = 'Not a valid action';
                    break;
            }
        }
        Util::json(['success' => $success, 'message' => $message], true);
    }

    public static function copy($steamid, $player, $csrf)
    {
        if (!self::validateCSRF($steamid, $csrf) || self::isBanned($steamid)) {
            Steam::Logout();
        }
        self::refreshCSRF($steamid);

        $success = Database::conn()->add("UPDATE `kload_users` AS `users` LEFT JOIN `kload_users` AS `source` ON `source`.`steamid` = '?' SET `users`.`custom_css` = `source`.`custom_css`, `users`.`settings` = `source`.`settings` WHERE `users`.`steamid` = '?'", [$player, $steamid])->execute();
        Util::log('action', $steamid.($success ? ' copied ' : ' attempted to copy ').'settings from '.$player);
        if ($success) {
            unset($_SESSION['settings']);
            $_SESSION = array_merge($_SESSION, self::get($_SESSION['steamid']));
            Cache::remove('player-'.$steamid);
            file_put_contents(APP_ROOT.'/data/users/'.$steamid.'.css', Util::minify(Database::conn()->select('SELECT `custom_css` FROM `kload_users`')->where("`steamid` = '?'", [$player])->execute()));
        }

        return $success;
    }

    public static function validateCSRF($steamid, $token)
    {
        return (bool) Database::conn()->select("SELECT (`steamid` = '?' AND `token` = '?' AND CURRENT_TIMESTAMP < `expires`) AS `valid` FROM `kload_sessions`", [$steamid, $token])->execute() ?? false;
    }

    public static function isBanned($steamid)
    {
        if (self::isSuper($steamid)) {
            return false;
        }

        return (int) Database::conn()->select('SELECT `banned` FROM `kload_users`')->where("`steamid` = '?'", [$steamid])->execute() ?? false;
    }

    public static function isSuper($steamid)
    {
        global $config;

        return in_array($steamid, $config['admins']);
    }

    public static function refreshCSRF($steamid)
    {
        $token = Util::token();
        $data = [
            [$steamid, $token],
        ];

        return Database::conn()->insert('INSERT INTO `kload_sessions` (`steamid`, `token`)')->values($data)->add("ON DUPLICATE KEY UPDATE `token` = '?'", [$token])->execute();
    }

    public static function get($steamid, ...$columns)
    {
        $logged_in = isset($_SESSION['steamid']);
        $super = (isset($_SESSION['steamid']) ? (self::isSuper($_SESSION['steamid'] ?? 0)) : 0);

        if (empty($columns)) {
            $columns = '`id`, `name`, `steamid`, `steamid2`, `steamid3`, `settings`, '.($logged_in ? '`custom_css` AS `css`,' : '').' `admin`, '.($super || $logged_in ? '`perms`,' : '\'[]\' as `perms`,').' `banned`, DATE_FORMAT(`registered`, \''.DATE_FORMAT.'\') AS `registered`';
        } else {
            foreach ($columns as $index => $column) {
                if (empty($column)) {
                    unset($columns[$index]);
                }
            }

            $columns = '`'.implode('`,`', array_values($columns)).'`';
        }

        return Database::conn()->select('SELECT '.$columns.' FROM `kload_users`')->where("`steamid` = '?'", [$steamid])->execute() ?? [];
    }

    public static function ban($steamid, $csrf)
    {
        if (!self::validateCSRF($_SESSION['steamid'], $csrf) || self::isBanned($_SESSION['steamid'])) {
            Steam::Logout();
        }
        self::refreshCSRF($_SESSION['steamid']);

        if (self::isAdmin($_SESSION['steamid']) && ($steamid != $_SESSION['steamid']) && !self::isSuper($steamid)) {
            if (self::isSuper($_SESSION['steamid']) || (array_key_exists('ban', $_SESSION['perms']) && self::isAdmin($_SESSION['steamid']) && !self::isAdmin($steamid))) {
                $banned = Database::conn()->add("UPDATE `kload_users` SET `banned` = 1 WHERE `steamid` = '?'", [$steamid])->execute();
                Util::log('action', $_SESSION['steamid'].($banned ? ' banned ' : ' attempted to ban ').$steamid);

                return $banned;
            }
        }

        return false;
    }

    public static function isAdmin($steamid)
    {
        return self::isSuper($steamid) || (int) Database::conn()->select('SELECT `admin` FROM `kload_users`')->where("`steamid` = '?'", [$steamid])->execute();
    }

    public static function unban($steamid, $csrf)
    {
        if (!self::validateCSRF($_SESSION['steamid'], $csrf) || self::isBanned($_SESSION['steamid'])) {
            Steam::Logout();
        }
        self::refreshCSRF($_SESSION['steamid']);

        if (self::isAdmin($_SESSION['steamid'])) {
            if (array_key_exists('unban', $_SESSION['perms']) || self::isSuper($_SESSION['steamid'])) {
                $unbanned = Database::conn()->add("UPDATE `kload_users` SET `banned` = 0 WHERE `steamid` = '?'", [$steamid])->execute();
                Util::log('action', $_SESSION['steamid'].($unbanned ? ' unbanned ' : ' attempted to unban ').$steamid);

                return $unbanned;
            }
        }

        return false;
    }

    public static function add($steamid)
    {
        global $config;

        $steam = Steam::User($steamid);
        $steamids = Steam::Convert($steamid);
        if (empty($steamids)) {
            $steamids = [
                'steamid'  => '',
                'steamid2' => '',
                'steamid3' => '',
            ];
        }
        $settings = json_encode([
            'theme'       => $config['loading_theme'] ?? 'default',
            'backgrounds' => [
                'enable'   => 1,
                'random'   => 0,
                'duration' => 5000,
                'fade'     => 750,
            ],
            'youtube'     => [
                'enable' => 0,
                'random' => 0,
                'list'   => [],
            ],
        ]);
        $data = [
            [$steam['personaname'], $steamids['steamid'], $steamids['steamid2'], $steamids['steamid3'], '[]', $settings],
        ];

        return Database::conn()->insert('INSERT IGNORE INTO `kload_users` (`name`, `steamid`, `steamid2`, `steamid3`, `perms`, `settings`)')->values($data)->execute();
    }

    public static function delete($steamid)
    {
        return Database::conn()->delete('kload_users')->where("`steamid` = '?'", [$steamid])->execute();
    }

    public static function update($steamid, $settings)
    {
        if (!self::validateCSRF($steamid, $settings['csrf']) || self::isBanned($steamid)) {
            Steam::Logout();
        }
        self::refreshCSRF($steamid);

        $css = (isset($settings['css']) ? filter_var($settings['css'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) : '');

        $settings['theme'] = Template::isLoadingTheme($settings['theme']) ? $settings['theme'] : 'default';

        $settings['backgrounds']['enable'] = (isset($settings['backgrounds']['enable']) ? (int) $settings['backgrounds']['enable'] : 0);
        $settings['backgrounds']['random'] = (isset($settings['backgrounds']['random']) ? (int) $settings['backgrounds']['random'] : 0);
        $settings['backgrounds']['duration'] = (isset($settings['backgrounds']['duration']) ? (int) $settings['backgrounds']['duration'] : 8000);
        $settings['backgrounds']['fade'] = (isset($settings['backgrounds']['fade']) ? (int) $settings['backgrounds']['fade'] : 750);

        $settings['youtube']['enable'] = (isset($settings['youtube']['enable']) ? (int) $settings['youtube']['enable'] : 0);
        $settings['youtube']['random'] = (isset($settings['youtube']['random']) ? (int) $settings['youtube']['random'] : 0);
        $settings['youtube']['volume'] = (isset($settings['youtube']['volume']) ? (int) $settings['youtube']['volume'] : 0);
        $settings['youtube']['list'] = (isset($settings['youtube']['list']) ? $settings['youtube']['list'] : []);
        if (count($settings['youtube']['list']) > 0) {
            $yt_ids = [];
            foreach ($settings['youtube']['list'] as $url) {
                $url = trim($url);
                $youtube_id = Util::YouTubeID($url);
                if ($youtube_id) {
                    $yt_ids[] = $youtube_id;
                }
            }
            $settings['youtube']['list'] = $yt_ids;
        }

        unset($settings['css']);
        unset($settings['csrf']);
        unset($settings['save']);

        $steamInfo = Steam::User($steamid);
        $data = $steamInfo ? $steamInfo : [];
        $data['settings'] = $settings;
        $data['settings']['backgrounds'] = json_encode($settings['backgrounds']);
        $data['settings']['youtube'] = json_encode($settings['youtube']);
        $steamids = Steam::Convert($steamid);
        if ($steamids) {
            $data = $data + $steamids;
        }

        $updated = Database::conn()->add("UPDATE `kload_users` SET `settings` = '?', `custom_css` = '?' WHERE `steamid` = '?'", [json_encode($settings), $css, $steamid])->execute() ?? false;

        if ($updated) {
            Cache::store('player-'.$steamid, $data, 0);
            if (!empty($css)) {
                file_put_contents(APP_ROOT.'/data/users/'.$steamid.'.css', Util::minify($css));
            } else {
                if (file_exists(APP_ROOT.'/data/users/'.$steamid.'.css')) {
                    unlink(APP_ROOT.'/data/users/'.$steamid.'.css');
                }
            }
        }

        return $updated;
    }

    public static function updatePerms($steamid, $post)
    {
        if (self::isSuper($_SESSION['steamid']) && !self::isSuper($steamid)) {
            if (!self::validateCSRF($_SESSION['steamid'], $post['csrf'])) {
                Steam::Logout();
            }
            self::refreshCSRF($_SESSION['steamid']);
            $result = Database::conn()->add("UPDATE `kload_users` SET `admin` = '?', `perms` = '?' WHERE `steamid` = '?'", [(isset($post['admin']) ? (int) $post['admin'] : 0), (isset($post['perms']) ? json_encode($post['perms']) : '[]'), $steamid])->execute();
            Util::log('action', $_SESSION['steamid'].($result ? ' set ' : ' attempted to set ').'permissions - ['.(isset($post['perms']) ? implode(',', $post['perms']) : 'N/A').'] and admin - '.(isset($post['admin']) ? (int) $post['admin'] : 0).' on '.$steamid);

            return $result;
        }

        return false;
    }

    public static function session($steamid)
    {
        $user = self::get($steamid);

        $steaminfo = Steam::User($steamid);
        if ($steaminfo) {
            if ($steaminfo['personaname'] !== $user['name']) {
                Database::conn()->add("UPDATE `kload_users` SET `name` = '?' WHERE `steamid` = '?'", [$steaminfo['personaname'], $steamid])->execute();
            }

            $_SESSION = ($_SESSION ?? []) + $steaminfo;
        }

        if (isset($user['settings'])) {
            $user['settings'] = json_decode($user['settings'], true);
        }
        $user['admin'] = (int) $user['admin'];
        $user['super'] = self::isSuper($steamid);
        if (isset($user['perms'])) {
            $user['perms'] = array_fill_keys(json_decode($user['perms']), 1);
        }

        $_SESSION = array_replace_recursive($_SESSION, $user);

        if ($_SESSION['settings'] !== $user['settings']) {
            $_SESSION['settings'] = $user['settings'];
        }
        self::refreshCSRF($steamid);
    }

    public static function getCSRF($steamid)
    {
        return Database::conn()->select('SELECT `token` FROM `kload_sessions`')->where("`steamid` = '?'", [$steamid])->execute() ?? 0;
    }

    public static function getPerms($friendly = false)
    {
        $perms = [
            'ban'            => 'Ban',
            'unban'          => 'Unban',
            'backgrounds'    => 'Backgrounds',
            'community_name' => 'Community Name',
            'description'    => 'Description',
            'rules'          => 'Rules',
            'messages'       => 'Messages',
            'staff'          => 'Staff',
            'youtube'        => 'YouTube',
            'music'          => 'Music',
        ];

        $keys = array_keys($perms);
        sort($keys);

        return !$friendly ? $keys : $perms;
    }

    public static function can($perm)
    {
        if (!isset($_SESSION['steamid'])) {
            return false;
        }

        return array_key_exists($perm, self::getCurrentPerms()) || self::isSuper($_SESSION['steamid']);
    }

    public static function getCurrentPerms()
    {
        return $_SESSION['perms'] ?? [];
    }
}
