<?php

namespace K_Load;

use Database;
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use Steam;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class Util
{
    public static function log($type = 'access', $content = null, $force = false)
    {
        if (!ENABLE_LOG && $force !== true) {
            return;
        }

        switch ($type) {
            case 'access':
                if (\strpos($_SERVER['REQUEST_URI'], 'raw') !== false) {
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
        $log_path = \sprintf('%sdata%slogs%s', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
        $log_folder = APP_ROOT.$log_path.$type;
        $log_loc = APP_ROOT.$log_path.$type.DIRECTORY_SEPARATOR.$log;

        if (!\file_exists($log_folder)) {
            if (!\mkdir($log_folder, 0700)) {
                if ($type != 'error') {
                    self::log('error', 'Failed to create folder for log type: '.$type);
                }
            } else {
                self::log('action', 'Created folder for log type: '.$type);
            }
        }

        $content = '['.\date('m-d-Y h:i:s A').'] ~ '.$content;
        $file = \fopen($log_loc, 'a');
        \fwrite($file, $content."\n");
        \fclose($file);

        if (\filesize($log_loc) >= 1048576) {
            $versions = \glob($log_loc.'.*');
            $recent_ver = \end($versions);
            $tmp = \explode('.', $recent_ver);

            $recent = (int) \end($tmp);
            \rename($log_loc, $log_loc.'.'.($recent + 1));
        }
    }

    public static function dump($var)
    {
        $cloner = new VarCloner();
        $dumper = new HtmlDumper();

        return $dumper->dump($cloner->cloneVar($var), true);
    }

    public static function rmDir($folder)
    {
        $content = \glob($folder.'/*');
        foreach ($content as $location) {
            if (\is_dir($location)) {
                self::rmdir($location);
            } else {
                \unlink($location);
            }
        }
        \rmdir($folder);
    }

    /**
     * Create a directory.
     *
     * @param  string
     *
     * @throws \Exception
     *
     * @return bool
     */
    public static function mkDir($directory)
    {
        if ($doesntExist = !\file_exists($directory)) {
            \set_error_handler(function () {
            });
            $doesntExist = !\mkdir($directory, 0775, true);
            \restore_error_handler();
            if ($doesntExist) {
                throw new \Exception('no perms to create directory, fix it');
            }
        }

        return !$doesntExist;
    }

    public static function installed()
    {
        return self::version();
    }

    public static function version($ignoreCache = false)
    {
        if (ENABLE_CACHE && !$ignoreCache) {
            $version = Cache::remember('version', 120, function () {
                $version = Database::conn()->select('SELECT `value` FROM `kload_settings`')->where("`name` = 'version'")->execute();

                return $version !== false ? $version : null;
            });
        } else {
            $version = Database::conn()->select('SELECT `value` FROM `kload_settings`')->where("`name` = 'version'")->execute();

            return $version !== false ? $version : null;
        }

        return $version;
    }

    public static function getSetting(...$keys)
    {
        $data = [];
        $where = '';

        \sort($keys);
        $length = \count($keys);
        for ($i = 0; $i < $length; $i++) {
            if (($i + 1) >= $length) {
                $where .= "`name` = '?' ";
            } else {
                $where .= "`name` = '?' OR ";
            }
        }

        $settings = Database::conn()->select('SELECT `name`,`value` FROM `kload_settings`')->where($where, $keys)->orderBy('name')->execute();

        if ($settings) {
            if (!isset($settings['name'])) {
                foreach ($settings as $index => $setting) {
                    $data[$keys[$index]] = $setting['value'];
                }
            } else {
                $data[$settings['name']] = $settings['value'];
            }
        }

        return \count($data) > 0 ? $data : [];
    }

    public static function updateSetting(array $settings, array $data, $csrf, $force = false)
    {
        if ((!User::validateCSRF($_SESSION['steamid'], $csrf) || User::isBanned($_SESSION['steamid'])) && !$force) {
            Steam::Logout();
        }

        if (!$force) {
            User::refreshCSRF($_SESSION['steamid']);
        }

        $i = 0;
        $sucess = true;

        foreach ($data as $insert) {
            $setting = $settings[$i];
            $i++;
            if (!User::can($setting)) {
                continue;
            }

            $result = Database::conn()->add("INSERT INTO `kload_settings` (`name`, `value`) VALUES ('?', '?') ON DUPLICATE KEY UPDATE `value` = '?'", [$setting, $insert, $insert])->execute();

            self::log('action', $_SESSION['steamid'].($result ? ' updated ' : ' attempted to update ').$setting);
            if (!$result) {
                $sucess = false;
            }
        }

        return $sucess;
    }

    public static function getBackgrounds($asArray = false)
    {
        $backgrounds = \glob(APP_ROOT.\sprintf('%sassets%simg%sbackgrounds%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
        $list = [];
        foreach ($backgrounds as $gamemode) {
            $images = \glob($gamemode.DIRECTORY_SEPARATOR.'*.{jpg,png}', GLOB_BRACE);

            if (\count($images) === 0) {
                continue;
            }

            foreach ($images as $index => $image) {
                $images[$index] = APP_PATH.\str_replace(DIRECTORY_SEPARATOR, '/', \str_replace(APP_ROOT, '', $image));
            }

            $gamemode = \explode(DIRECTORY_SEPARATOR, $gamemode);
            $gamemode = \end($gamemode);
            $list[$gamemode] = $images;
        }

        return !$asArray ? \json_encode($list) : $list;
    }

    public static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && \strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    public static function isUrl($url)
    {
        \set_error_handler(function () {
        });
        $headers = \get_headers($url);
        $httpCode = \substr($headers[0], 9, 3);
        \restore_error_handler();

        return $httpCode >= 200 && $httpCode <= 400;
    }

    public static function json($data, $header = false, $formatted = false)
    {
        if ($header) {
            \header('Content-Type: application/json');
        }
        echo \json_encode($data, ($formatted ? JSON_PRETTY_PRINT : 0));
        if ($header) {
            die();
        }
    }

    public static function minify($css)
    {
        $minifier = new \MatthiasMullie\Minify\CSS();
        $minifier->add($css);

        return $minifier->minify();
    }

    public static function redirect($url)
    {
        if (self::startsWith('/', $url)) {
            $url = APP_PATH.$url;
        }
        \header('Location: '.$url, true, 302);
        die();
    }

    public static function startsWith($search, $string)
    {
        return \strpos($string, $search) === 0;
    }

    public static function token()
    {
        return \hash('sha256', \bin2hex(\random_bytes(16)));
    }

    public static function var_export($var, $indent = '')
    {
        switch (\gettype($var)) {
            case 'string':
                return '"'.\addcslashes($var, "\\\$\"\r\n\t\v\f").'"';
            case 'array':
                $indexed = \array_keys($var) === \range(0, \count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent	"
                        .($indexed ? '' : self::var_export($key).' => ')
                        .self::var_export($value, "$indent	");
                }

                return "[\n".\implode(",\n", $r)."\n".$indent.']';
            case 'boolean':
                return $var ? 'TRUE' : 'FALSE';
            default:
                return \var_export($var, true);
        }
    }

    public static function YouTubeID($url)
    {
        $url = \urldecode(\rawurldecode($url));
        \preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $url, $match);

        return $match[1] ?? null;
    }

    public static function array_for_JS(array $array)
    {
        return '`'.\implode('`,`', $array).'`';
    }

    public static function to_top(&$array, $key)
    {
        $temp = [$key => $array[$key]];
        unset($array[$key]);
        $array = $temp + $array;
    }

    public static function flash($key, $value)
    {
        if (\session_status() == PHP_SESSION_ACTIVE) {
            $_SESSION['flash'][$key] = $value;
        }
    }
}
