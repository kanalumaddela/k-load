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

namespace KLoad;

use InvalidArgumentException;
use function file_exists;
use function is_array;
use function is_null;
use function is_numeric;
use function sprintf;
use function vsprintf;

class Lang
{
    const LANG_FOLDER = APP_ROOT.'/inc/lang';

    public $currentLang = 'en';

    protected $lang = [];

    protected static $fallback = [];

    protected static $booted = false;

    public function __construct($language = 'en')
    {
        static::boot();

        if (!static::exists($language)) {
            throw new InvalidArgumentException('Language `'.$language.'` not found in `inc/lang/` folder');
        }

        $this->currentLang = $language;
        $this->lang = include static::LANG_FOLDER.'/'.$language.'.php';
    }

    public static function boot()
    {
        if (static::$booted) {
            return;
        }

        static::$booted = true;

        static::$fallback = include static::LANG_FOLDER.'/en.php';
    }

    public function getCurrentLang(): string
    {
        return $this->currentLang;
    }

    /**
     * @param $lang
     *
     * @return bool
     */
    public static function exists($lang)
    {
        return file_exists(APP_ROOT.'/inc/lang/'.$lang.'.php');
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return mixed|string|null
     */
    public function get($key, $default = null)
    {
        $lang = isset($this->lang[$key]) && !empty($this->lang[$key]) ? $this->lang[$key] : (isset(static::$fallback[$key]) && !empty(static::$fallback[$key]) ? static::$fallback[$key] : $key);

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
