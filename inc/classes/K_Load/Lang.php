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

use Exception;
use const APP_ROOT;
use function file_exists;
use function is_array;
use function is_null;
use function is_numeric;
use function sprintf;
use function vsprintf;

class Lang
{
    const LANG_FOLDER = APP_ROOT.'/inc/lang';

    protected static $lang = [];

    protected static $fallback = [];

    public static function init($language = 'en')
    {
        if (!self::exists($language)) {
            throw new Exception('Language `'.$language.'` not found in `inc/lang/` folder');
        }

        self::$lang = include self::LANG_FOLDER.'/'.$language.'.php';
        self::$fallback = include self::LANG_FOLDER.'/en.php';
    }

    public static function exists($lang)
    {
        return file_exists(APP_ROOT.'/inc/lang/'.$lang.'.php');
    }

    public static function get($key, $default = null)
    {
        $lang = isset(self::$lang[$key]) && !empty(self::$lang[$key]) ? self::$lang[$key] : (isset(self::$fallback[$key]) && !empty(self::$fallback[$key]) ? self::$fallback[$key] : $key);

        if ($lang === $key && !is_numeric($default)) {
            if (!is_null($default) && !is_array($default)) {
                $lang = $default;
            }

            return $lang;
        }

        if (is_array($lang) && is_numeric($default)) {
            $lang = sprintf($default == 1 ? $lang[0] : $lang[1], $default);
        } else {
            $lang = is_array($default) ? vsprintf($lang, $default) : sprintf($lang, $default);
        }

        return $lang;
    }
}
