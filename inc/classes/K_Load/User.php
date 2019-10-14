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
use Exception;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use Steam;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_replace_recursive;
use function boolval;
use function ceil;
use function count;
use function date;
use function file_exists;
use function file_put_contents;
use function filter_var;
use function header;
use function headers_sent;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function json_decode;
use function json_encode;
use function sort;
use function str_replace;
use function strtotime;
use function unlink;
use const APP_PATH;
use const DATE_FORMAT;

class User
{
    public static $columns = [
        'id',
        'name',
        'steamid',
        'steamid2',
        'steamid3',
        'admin',
        'perms',
        'settings',
        'banned',
        'registered',
    ];

    private static $retrievedTokens = [];

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
        self::validateCSRF($steamid, $csrf);

        $success = Database::conn()->add("UPDATE `kload_users` AS `users` LEFT JOIN `kload_users` AS `source` ON `source`.`steamid` = '?' SET `users`.`custom_css` = `source`.`custom_css`, `users`.`settings` = `source`.`settings` WHERE `users`.`steamid` = '?'", [$player, $steamid])->execute();
        Util::log('action', $steamid.($success ? ' copied ' : ' attempted to copy ').'settings from '.$player);
        if ($success) {
            unset($_SESSION['settings']);
            $_SESSION = array_merge($_SESSION, self::get($_SESSION['steamid']));
            Cache::remove('player-'.$steamid);
            file_put_contents(APP_ROOT.'/data/users/'.$steamid.'.css', self::getUserData($_SESSION['steamid'], 'custom_css'));
        }

        return $success;
    }

    public static function validateCSRF($steamid, $token)
    {
        $valid = self::isValidCSRF($steamid, $token);
        $token = self::refreshCSRF($steamid);
        if (!$valid) {
            if (Util::isAjax()) {
                if (headers_sent() === false) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'data' => ['message' => 'CSRF token failed, try again', 'csrf' => $token]]);
                }
                die();
            }

            Util::flash('alerts', ['message' => 'CSRF token failed, try again', 'css' => 'red']);

            if (!empty($_POST)) {
                unset($_POST['csrf']);
                Util::flash('alerts', 'Attempted to recover changes made, please re-save/resubmit these changes');
                Util::flash('post', $_POST);
            }

            Util::redirect(str_replace(APP_PATH, '', $_SERVER['REQUEST_URI']));
        }
    }

    public static function isValidCSRF($steamid, $token)
    {
        $valid = Database::conn()->select("SELECT (`steamid` = '?' AND `token` = '?' AND CURRENT_TIMESTAMP < `expires`) AS `valid` FROM `kload_sessions`", [$steamid, $token])->execute() ?? 0;
        $valid = boolval((int) $valid);

        return $valid;
    }

    public static function refreshCSRF($steamid)
    {
        $token = Util::token();
        $data = [
            [$steamid, $token],
        ];

        if (isset($_SESSION['steamid']) && $_SESSION['steamid'] === $steamid) {
            $_SESSION['csrf'] = $token;
        }

        self::$retrievedTokens[$steamid] = $token;

        return Database::conn()->insert('INSERT INTO `kload_sessions` (`steamid`, `token`)')->values($data)->add("ON DUPLICATE KEY UPDATE `token` = '?'", [$token])->execute();
    }

    public static function get($steamid, ...$columns)
    {
        if (empty($columns)) {
            $columns = self::$columns;
        }

        $selectColumns = '`'.implode('`,`', $columns).'`';

        $data = Database::conn()->select('SELECT '.$selectColumns.' FROM `kload_users`')->where("`steamid` = '?'", [$steamid])->limit(1)->execute();

        if (empty($data) || !$data) {
            $data = [];
        }

        if (isset($data['perms'])) {
            $data['perms'] = json_decode($data['perms'], true);
            if ($data['perms'] === false || empty($data['perms'])) {
                $data['perms'] = [];
            }
        }

        if (isset($data['registered'])) {
            $data['registered'] = date(DATE_FORMAT, strtotime($data['registered']));
        }

        return $data;
    }

    public static function getUserData($steamid, $column)
    {
        $column = '`'.$column.'`';

        return Database::conn()->select('SELECT '.$column.' FROM `kload_users`')->where("`steamid` = '?'", [$steamid])->limit(1)->execute() ?? [];
    }

    public static function ban($steamid, $csrf)
    {
        self::validateCSRF($_SESSION['steamid'], $csrf);

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

    public static function isSuper($steamid)
    {
        global $config;

        return in_array($steamid, $config['admins']);
    }

    public static function unban($steamid, $csrf)
    {
        self::validateCSRF($_SESSION['steamid'], $csrf);

        if (self::isAdmin($_SESSION['steamid'])) {
            if (array_key_exists('unban', $_SESSION['perms']) || self::isSuper($_SESSION['steamid'])) {
                $unbanned = Database::conn()->add("UPDATE `kload_users` SET `banned` = 0 WHERE `steamid` = '?'", [$steamid])->execute();
                Util::log('action', $_SESSION['steamid'].($unbanned ? ' unbanned ' : ' attempted to unban ').$steamid);

                return $unbanned;
            }
        }

        return false;
    }

    public static function validatePerm($perm)
    {
        if (!self::can($perm)) {
            if (Util::isAjax()) {
                if (headers_sent() === false) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'data' => ['message' => 'Permission `'.$perm.'` not given.']]);
                }
                die();
            }

            Util::flash('alerts', 'You do not have the `'.$perm.'` permissions');
            Util::redirect('/dashboard/admin');
        }
    }

    public static function can($perm)
    {
        if (!isset($_SESSION['steamid']) || !isset($_SESSION['perms'])) {
            return false;
        }

        if (DEMO_MODE || self::isSuper($_SESSION['steamid'])) {
            return true;
        }

        return in_array($perm, $_SESSION['perms']);
    }

    public static function isBanned($steamid)
    {
        if (self::isSuper($steamid)) {
            return false;
        }

        return (int) Database::conn()->select('SELECT `banned` FROM `kload_users`')->where("`steamid` = '?'", [$steamid])->execute() ?? false;
    }

    public static function add($steamid, $forceAdmin = false)
    {
        global $config;

        $steam = Steam::User($steamid);
        $steamids = Steam::Convert($steamid);

        $globalSettings = Util::getSetting('backgrounds', 'youtube', 'music');

        if (isset($globalSettings['backgrounds'])) {
            $globalSettings['backgrounds'] = json_encode($globalSettings['backgrounds'], true);
        }
        if (isset($globalSettings['youtube'])) {
            $globalSettings['youtube'] = json_encode($globalSettings['youtube'], true);
        }
        if (isset($globalSettings['music'])) {
            $globalSettings['music'] = json_encode($globalSettings['music'], true);
        }

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
                'enable'   => $globalSettings['backgrounds']['enable'] ?? 1,
                'random'   => $globalSettings['backgrounds']['random'] ?? 0,
                'duration' => $globalSettings['backgrounds']['duration'] ?? 5000,
                'fade'     => $globalSettings['backgrounds']['fade'] ?? 750,
            ],
            'youtube'     => [
                'enable' => $globalSettings['youtube']['enable'] ?? $globalSettings['music']['enable'] ?? 0,
                'random' => $globalSettings['youtube']['random'] ?? $globalSettings['music']['random'] ?? 0,
                'list'   => [],
            ],
        ]);

        $admin = (int) $forceAdmin;

        $data = [
            [$steam['personaname'], $steamids['steamid'], $steamids['steamid2'], $steamids['steamid3'], $admin, json_encode([]), $settings],
        ];

        if ($id = Database::conn()->insert('INSERT IGNORE INTO `kload_users` (`name`, `steamid`, `steamid2`, `steamid3`, `admin`, `perms`, `settings`)')->values($data)->execute()) {
            if (is_bool($id)) {
                throw new Exception('$id must be the inserted id, bool given');
            }

            return array_merge([
                'id'       => $id,
                'name'     => $steam['personaname'],
                'admin'    => 0,
                'perms'    => [],
                'settings' => json_decode($settings, true),
            ], $steamids);
        }

        return false;
    }

    public static function delete($steamid)
    {
        return Database::conn()->delete('kload_users')->where("`steamid` = '?'", [$steamid])->execute();
    }

    public static function update($steamid, $settings)
    {
        self::validateCSRF($steamid, $settings['csrf']);

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
                file_put_contents(APP_ROOT.'/data/users/'.$steamid.'.css', $css);
            } else {
                if (file_exists(APP_ROOT.'/data/users/'.$steamid.'.css')) {
                    unlink(APP_ROOT.'/data/users/'.$steamid.'.css');
                }
            }

//            if ($steamid === $_SESSION['steamid']) {
//                self::session($steamid);
//            }
        }

        return $updated;
    }

    public static function updatePerms($steamid, $post)
    {
        if (self::isSuper($_SESSION['steamid']) && !self::isSuper($steamid)) {
            self::validateCSRF($_SESSION['steamid'], $post['csrf']);
            $result = Database::conn()->add("UPDATE `kload_users` SET `admin` = '?', `perms` = '?' WHERE `steamid` = '?'", [(isset($post['admin']) ? (int) $post['admin'] : 0), (isset($post['perms']) ? json_encode($post['perms']) : '[]'), $steamid])->execute();
            Util::log('action', $_SESSION['steamid'].($result ? ' set ' : ' attempted to set ').'permissions - ['.(isset($post['perms']) ? implode(',', $post['perms']) : 'N/A').'] and admin - '.(isset($post['admin']) ? (int) $post['admin'] : 0).' on '.$steamid);

            return $result;
        }

        return false;
    }

    public static function session($user, $dontRefresh = false)
    {
        if (!is_array($user)) {
            $user = self::get($user);
        }

        $steaminfo = Steam::User($user['steamid']);
        if ($steaminfo) {
            if ($steaminfo['personaname'] !== $user['name']) {
                Database::conn()->add("UPDATE `kload_users` SET `name` = '?' WHERE `steamid` = '?'", [$steaminfo['personaname'], $user['steamid']])->execute();
            }
            unset($steaminfo['steamid']);

            $_SESSION = ($_SESSION ?? []) + $steaminfo;
        }

        if (isset($user['settings'])) {
            $user['settings'] = json_decode($user['settings'], true);
        }
        $user['admin'] = $user['admin'] == 0 ? self::isSuper($user['steamid']) : boolval((int) $user['admin']);
        $user['super'] = self::isSuper($user['steamid']);

        if (!$dontRefresh) {
            self::refreshCSRF($user['steamid']);
        }

        $user['csrf'] = self::getCSRF($user['steamid']);

        $_SESSION = array_replace_recursive($_SESSION, $user);

//        if ($_SESSION['settings'] !== $user['settings']) {
//            $_SESSION['settings'] = $user['settings'];
//        }
    }

    public static function getCSRF($steamid = null)
    {
        if (empty($steamid)) {
            $steamid = $_SESSION['steamid'] ?? null;
        }

        if (!$steamid) {
            return 0;
        }

        if (isset(self::$retrievedTokens[$steamid])) {
            return self::$retrievedTokens[$steamid];
        }

        $token = Database::conn()->select('SELECT `token` FROM `kload_sessions`')->where("`steamid` = '?'", [$steamid])->execute() ?? 0;

        if ($token !== 0) {
            self::$retrievedTokens[$steamid] = $token;
        }

        return $token;
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

    public static function canOr(...$perms)
    {
        if (!isset($_SESSION['steamid']) || !isset($_SESSION['perms'])) {
            return false;
        }

        if (DEMO_MODE || self::isSuper($_SESSION['steamid'])) {
            return true;
        }

        foreach ($perms as $perm) {
            if (self::can($perm)) {
                return true;
            }
        }

        return false;
    }

    public static function canAnd(...$perms)
    {
        if (!isset($_SESSION['steamid']) || !isset($_SESSION['perms'])) {
            return false;
        }

        if (DEMO_MODE || self::isSuper($_SESSION['steamid'])) {
            return true;
        }

        foreach ($perms as $perm) {
            if (!self::can($perm)) {
                return false;
            }
        }

        return true;
    }

    public static function cant($perm)
    {
        return !self::can($perm);
    }

    public static function getCurrentPerms()
    {
        return $_SESSION['perms'] ?? [];
    }
}
