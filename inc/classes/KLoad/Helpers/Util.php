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

namespace KLoad\Helpers;

use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\UrlWindow;
use Illuminate\Support\Str;
use kanalumaddela\SteamLogin\SteamLogin;
use KLoad\Facades\Config;
use function array_filter;
use function array_slice;
use function bin2hex;
use function count;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function file_exists;
use function file_put_contents;
use function glob;
use function implode;
use function is_array;
use function is_dir;
use function is_null;
use function json_decode;
use function mkdir;
use function preg_match;
use function random_bytes;
use function restore_error_handler;
use function rmdir;
use function rtrim;
use function scandir;
use function set_error_handler;
use function sprintf;
use function str_replace;
use function unlink;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const DIRECTORY_SEPARATOR;
use const KLoad\APP_PATH;
use const KLoad\APP_ROOT;

class Util
{
    public static function token()
    {
        return self::hash();
    }

    public static function hash($length = 16)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Create a directory.
     *
     * @param string
     * @param bool $includeHtaccess
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function mkDir(string $directory, bool $includeHtaccess = false): bool
    {
        $directory = rtrim($directory, '/');

        if ($doesntExist = !file_exists($directory)) {
            set_error_handler(function () {
            });
            $doesntExist = !mkdir($directory, 0774, true);
            restore_error_handler();
            if ($doesntExist) {
                throw new Exception('No permissions to create directory `'.$directory.'`');
            }
        }

        if (file_exists($directory) && $includeHtaccess && !file_exists($directory.'/.htaccess')) {
            file_put_contents($directory.'/.htaccess', "options -indexes\ndeny from all");
        }

        return !$doesntExist;
    }

    /**
     * Delete a directory and its contents.
     *
     * @param string $folder
     */
    public static function rmDir($folder)
    {
        $content = glob($folder.'/*');

        foreach ($content as $location) {
            is_dir($location) ? self::rmdir($location) : unlink($location);
        }

        rmdir($folder);
    }

    public static function getBackgrounds()
    {
        $backgrounds = [];
        $backgroundUrlPath = '/assets/img/backgrounds';
        $backgroundRoot = APP_ROOT.$backgroundUrlPath;

        foreach (scandir($backgroundRoot) as $item) {
            if ($item === '.' || $item === '..' || !is_dir($backgroundRoot.'/'.$item)) {
                continue;
            }

            $bgGamemodeUrlPath = $backgroundUrlPath.'/'.$item;

            if (count($files = static::listDir($backgroundRoot.'/'.$item)) > 0) {
                $backgrounds[$item] = [];
                foreach ($files as $bg) {
                    if (!Str::endsWith($bg, ['.jpg', '.jpeg', '.png'])) {
                        continue;
                    }

                    $backgrounds[$item][] = APP_PATH.$bgGamemodeUrlPath.'/'.$bg;
                }
            }
        }

        return $backgrounds;
    }

    /**
     * List a folder's files by default.
     *
     * @param      $dir
     * @param bool $includeFolders
     *
     * @return array
     */
    public static function listDir($dir, bool $includeFolders = false)
    {
        $dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);

        if (static::isLinux() && !Str::startsWith($dir, '/')) {
            $dir = APP_ROOT.DIRECTORY_SEPARATOR.$dir;
        }

        $items = array_slice(scandir($dir), 2);
        $filtered = [];

        foreach ($items as $item) {
            if (is_dir($dir.DIRECTORY_SEPARATOR.$item) && !$includeFolders) {
                continue;
            }
            $filtered[] = $item;
        }

        return $filtered;
    }

    public static function isLinux()
    {
        return DIRECTORY_SEPARATOR === '/';
    }

    public static function convertIniStringToBytes($value)
    {
        $conversions = [
            'K' => 1024,
            'M' => 1048576,
            'G' => 1073741824,
        ];

        preg_match('/(\d+)([KMG])/', $value, $matches);

        if (!isset($matches[1], $matches[2])) {
            return 0;
        }

        return $matches[1] * $conversions[$matches[2]];
    }

    public static function paginateFix(LengthAwarePaginator $paginator)
    {
        $window = UrlWindow::make($paginator);

        return array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }

    public static function getPlayersInfo($steamids, $useSteamidKeys = false)
    {
        if (is_array($steamids)) {
            $steamids = implode(',', $steamids);
        }

        $url = sprintf(SteamLogin::STEAM_API, Config::get('apikeys.steam'), $steamids);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        $data = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($data, true);

        if (is_null($data)) {
            $data = [];
        }

        if (isset($data['response']['players'])) {
            $data = $data['response']['players'];
        }

        return $useSteamidKeys ? static::fixSteamInfo($data) : $data;
    }

    public static function fixSteamInfo(array $data): array
    {
        $fixed = [];

        foreach ($data as $player) {
            $fixed['player-' . $player['steamid']] = $player;
        }

        return $fixed;
    }

    public static function YouTubeID($url)
    {
        $url = urldecode(rawurldecode($url));
        preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $url, $match);

        return $match[1] ?? null;
    }
}
