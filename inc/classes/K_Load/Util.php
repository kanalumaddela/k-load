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

use function addcslashes;
use const APP_HOST;
use const APP_PATH;
use const APP_ROOT;
use function array_column;
use function array_diff;
use function array_fill;
use function array_keys;
use function bin2hex;
use function count;
use Database;
use function date;
use const ENABLE_CACHE;
use function end;
use Exception;
use function explode;
use function fclose;
use function file_exists;
use function file_put_contents;
use function filesize;
use function fopen;
use function fwrite;
use function get_headers;
use function gettype;
use function glob;
use function header;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_file;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use function json_encode;
use function mkdir;
use function preg_match;
use function random_bytes;
use function range;
use function rawurldecode;
use function rename;
use function restore_error_handler;
use function rmdir;
use function rtrim;
use function session_status;
use function set_error_handler;
use function sprintf;
use Steam;
use function strpos;
use function strtolower;
use function substr;
use function unlink;
use function urldecode;
use function var_dump;
use function var_export;

class Util
{
    public static $version = null;

    public static function rmDir($folder)
    {
        $content = glob($folder.'/*');
        foreach ($content as $location) {
            if (is_dir($location)) {
                self::rmdir($location);
            } else {
                unlink($location);
            }
        }

        rmdir($folder);
    }

    public static function installed()
    {
        return self::version();
    }

    public static function version()
    {
        if (!file_exists(APP_ROOT.'/data/config.php')) {
            return;
        }

        if (!self::$version) {
            $version = Database::conn()->select('SELECT `value` FROM `kload_settings`')->where("`name` = 'version'")->execute();

            self::$version = $version !== false ? $version : null;
        }

        return self::$version;
    }

    public static function getSettingKeys($ignoreCache = false)
    {
        if (ENABLE_CACHE && !$ignoreCache) {
            $keys = Cache::remember('setting-keys', 0, function () {
                $tmp = Database::conn()->select('SELECT `name` FROM `kload_settings`')->orderBy('name')->execute(false);

                return array_column($tmp, 'name');
            });
        } else {
            $keys = array_column(Database::conn()->select('SELECT `name` FROM `kload_settings`')->orderBy('name')->execute(false), 'name');
        }

        return $keys;
    }

    public static function updateSetting(array $settings, array $data, $csrf, $force = false)
    {
        if (!isset($_SESSION['steamid']) && !$force) {
            Steam::Logout();
        }

        if (!$force) {
            User::validateCSRF($_SESSION['steamid'], $csrf);
            User::refreshCSRF($_SESSION['steamid']);
        }

        $i = 0;
        $success = true;

        foreach ($data as $insert) {
            $setting = $settings[$i];
            $i++;
            if (!User::can($setting)) {
                continue;
            }

            $result = Database::conn()->add("INSERT INTO `kload_settings` (`name`, `value`) VALUES ('?', '?') ON DUPLICATE KEY UPDATE `value` = '?'", [$setting, $insert, $insert])->execute();

            self::log('action', $_SESSION['steamid'].($result ? ' updated ' : ' attempted to update ').$setting);
            if (!$result) {
                $success = false;
            }
        }

        if ($success) {
            Cache::store('settings', self::getSettings(), 0);
            Cache::remove('loading-screen');
        }

        return $success;
    }

    public static function saveSetting($setting, $value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $success = Database::conn()->add("INSERT INTO `kload_settings` (`name`, `value`) VALUES ('?', '?') ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)", [$setting, $value])->execute();
        if ($success) {
            Cache::store('settings', self::getSettings(), 0);
        }

        return $success;
    }

    public static function saveSettings(array $settings)
    {
        $parameters = implode(',', array_fill(0, 2, '\'?\''));
        $valueParams = implode(',', array_fill(0, count($settings), '('.$parameters.')'));

        var_dump($valueParams);
        die();

        $inserts = [];

        foreach ($settings as $setting => $value) {
            $inserts[] = $settings;
            $inserts[] = $value;
        }

        return Database::conn()->add('INSERT INTO `kload_settings` (`name`, `value`) VALUES '.$valueParams.' ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', $inserts)->execute();
    }

    public static function log($type = 'access', $content = null, $force = false)
    {
        if (!ENABLE_LOG && $force !== true) {
            return;
        }

        switch ($type) {
            case 'access':
                if (strpos($_SERVER['REQUEST_URI'], 'raw') !== false) {
                    return;
                }
                $content = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' - '.$_SERVER['REMOTE_ADDR'];
                break;
            default:
                if (!$content) {
                    return;
                }
                break;
        }

        $log = $type.'.log';
        $log_path = sprintf('%sdata%slogs%s', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
        $log_folder = APP_ROOT.$log_path.$type;
        $log_loc = APP_ROOT.$log_path.$type.DIRECTORY_SEPARATOR.$log;

        self::mkDir($log_folder, true);

        $content = '['.date('m-d-Y h:i:s A').'] ~ '.$content;
        $file = fopen($log_loc, 'a');
        fwrite($file, $content."\n");
        fclose($file);

        if (filesize($log_loc) >= 1048576) {
            $versions = glob($log_loc.'.*');
            $recent_ver = end($versions);
            $tmp = explode('.', $recent_ver);

            $recent = (int) end($tmp);
            rename($log_loc, $log_loc.'.'.($recent + 1));
        }
    }

    /**
     * Create a directory.
     *
     * @param string
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function mkDir($directory, $includeHtaccess = false)
    {
        $directory = rtrim($directory, '/');

        if ($doesntExist = !file_exists($directory)) {
            set_error_handler(function () {
            });
            $doesntExist = !mkdir($directory, 0775, true);
            restore_error_handler();
            if ($doesntExist) {
                throw new Exception('no perms to create directory, fix it');
            }

            if (!$doesntExist && $includeHtaccess) {
                file_put_contents($directory.'/.htaccess', "options -indexes\ndeny from all");
            }
        } elseif ($includeHtaccess && !file_exists($directory.'/.htaccess')) {
            file_put_contents($directory.'/.htaccess', "options -indexes\ndeny from all");
        }

        return !$doesntExist;
    }

    public static function getSettings()
    {
        return self::getSetting();
    }

    public static function getSetting(...$keys)
    {
        $queryBuilder = Database::conn()->select('SELECT `name`,`value` FROM `kload_settings`');

        if (!empty($keys)) {
            $length = count($keys);
            $where = '`name` IN ('.implode(',', array_fill(0, $length, '\'?\'')).')';
            $queryBuilder->where($where, $keys);
        }

        $data = [];
        $settings = $queryBuilder->orderBy('name')->execute(false);

        if ($settings) {
            if (isset($settings['name'])) {
                $data[$settings['name']] = $settings['value'];
            } else {
                foreach ($settings as $setting) {
                    $data[$setting['name']] = $setting['value'];
                }
            }
        }

        return $data;
    }

    public static function getBackgrounds($asArray = false)
    {
        $backgroundsRoot = APP_ROOT.'/assets/img/backgrounds/';

        $backgrounds = array_diff(scandir($backgroundsRoot), ['.', '..']);

        $list = [];
        foreach ($backgrounds as $gamemode) {
            if (!is_dir($backgroundsRoot.$gamemode)) {
                continue;
            }

            $images = array_diff(scandir($backgroundsRoot.$gamemode), ['.', '..']);

            if (count($images) === 0) {
                continue;
            }

            $imagesFixed = [];
            foreach ($images as $image) {
                if (!is_file($backgroundsRoot.$gamemode.'/'.$image)) {
                    continue;
                }

                if (in_array(substr($image, -3, 3), ['jpg', 'png'])) {
                    $imagesFixed[] = APP_PATH.'/assets/img/backgrounds/'.$gamemode.'/'.$image;
                }
            }

            $list[$gamemode] = $imagesFixed;
        }

        return !$asArray ? json_encode($list) : $list;
    }

    public static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    public static function isUrl($url)
    {
        set_error_handler(function () {
        });
        $headers = get_headers($url);
        $httpCode = substr($headers[0], 9, 3);
        restore_error_handler();

        return $httpCode >= 200 && $httpCode <= 400;
    }

    public static function json($data, $header = false, $formatted = false)
    {
        if ($header) {
            header('Content-Type: application/json');
        }
        echo json_encode($data, ($formatted ? JSON_PRETTY_PRINT : 0));
        if ($header) {
            die();
        }
    }

    public static function redirect($url)
    {
        if (self::startsWith('/', $url)) {
            $url = APP_HOST.APP_PATH.$url;
        }
        header('Location: '.$url, true, 302);
        die();
    }

    public static function startsWith($search, $string)
    {
        return strpos($string, $search) === 0;
    }

    public static function token()
    {
        return self::hash();
    }

    public static function hash($length = 16)
    {
        return bin2hex(random_bytes($length));
    }

    public static function var_export($var, $indent = '')
    {
        switch (gettype($var)) {
            case 'string':
                return '"'.addcslashes($var, "\\\$\"\r\n\t\v\f").'"';
            case 'array':
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        .($indexed ? '' : self::var_export($key).' => ')
                        .self::var_export($value, "$indent    ");
                }

                return "[\n".implode(",\n", $r)."\n".$indent.']';
            case 'boolean':
                return $var ? 'true' : 'false';
            default:
                return var_export($var, true);
        }
    }

    public static function YouTubeID($url)
    {
        $url = urldecode(rawurldecode($url));
        preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $url, $match);

        return $match[1] ?? null;
    }

    public static function to_top(&$array, $key)
    {
        $temp = [$key => $array[$key]];
        unset($array[$key]);
        $array = $temp + $array;
    }

    public static function flash($key, $value)
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            if ($key == 'alerts') {
                if (!isset($_SESSION['flash']['alerts'])) {
                    $_SESSION['flash']['alerts'] = [];
                }

                if (!is_array($value)) {
                    $value = [
                        'message'  => $value,
                        'duration' => 4000,
                        'css'      => '',
                    ];
                } else {
                    $value['duration'] = $value['duration'] ?? 5000;
                    $value['css'] = $value['css'] ?? '';
                }

                $_SESSION['flash']['alerts'][] = $value;
            } else {
                $_SESSION['flash'][$key] = $value;
            }
        } else {
            echo 'session not active';
            die();
        }
    }

    public static function createConfig(array $config)
    {
        file_put_contents(APP_ROOT.'/data/config.php', '<?php'."\n".'return '.self::var_export($config).';'."\n");
    }

    public static function convertIniStringToBytes($value)
    {
        $conversions = [
            'K' => 1024,
            'M' => 1048576,
            'G' => 1073741824,
        ];

        preg_match('/(\d+)(K|M|G)/', $value, $matches);

        if (!isset($matches[1], $matches[2])) {
            return 0;
        }

        return $matches[1] * $conversions[$matches[2]];
    }
}
