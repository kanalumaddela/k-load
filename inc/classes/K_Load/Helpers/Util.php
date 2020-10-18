<?php
/**
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2020 Maddela
 * @license   MIT
 */

namespace K_Load\Helpers;

use Exception;
use Illuminate\Support\Str;
use function array_slice;
use function bin2hex;
use function count;
use function file_exists;
use function file_put_contents;
use function glob;
use function is_dir;
use function mkdir;
use function preg_match;
use function random_bytes;
use function restore_error_handler;
use function rmdir;
use function rtrim;
use function scandir;
use function set_error_handler;
use function str_replace;
use function unlink;
use const DIRECTORY_SEPARATOR;
use const K_Load\APP_PATH;
use const K_Load\APP_ROOT;

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
            $doesntExist = !mkdir($directory, 0774, true);
            restore_error_handler();
            if ($doesntExist) {
                throw new Exception('no perms to create directory, fix it');
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
}
