<?php

namespace K_Load;

use Exception;
use const APP_ROOT;
use function file_exists;
use function is_array;
use function is_null;
use function vsprintf;

class Lang
{
    const LANG_FOLDER = APP_ROOT.'/inc/lang';

    protected static $lang = [];

    protected static $fallback = [];

    public static function init($language = 'en')
    {
        if (!self::exists($language)) {
            throw new Exception('Language `'.$language.'` not found');
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

        if ($lang === $key) {
            if (!is_null($default) && !is_array($default)) {
                $lang = $default;
            }

            return $lang;
        }

        $lang = vsprintf($lang, $default);

        return $lang;
    }
}
