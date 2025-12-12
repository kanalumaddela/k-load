<?php

/*
 * K-Load v2 (https://demo.maddela.org/k-load/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/k-load-v2
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2025 kanalumaddela
 * @license   MIT
 */

namespace KLoad;

use InvalidArgumentException;

class Lang
{
    public const LANG_FOLDER = APP_ROOT.'/inc/lang';

    public string $currentLang = 'en';

    protected array $lang = [];

    protected static array $fallback = [];

    protected static bool $booted = false;

    protected static bool $debug = DEBUG;

    protected array $missing = [];
    protected $missingFile;

    public function __construct($language = 'en')
    {
        static::boot();

        if (!static::exists($language)) {
            throw new InvalidArgumentException('Language `'.$language.'` not found in `inc/lang/` folder');
        }

        $this->currentLang = $language;
        $this->lang = include static::LANG_FOLDER.'/'.$language.'.php';

        if (static::$debug) {
            $loc = APP_ROOT.'/inc/lang/missing/'.$language.'.txt';
            $this->missingFile = \fopen($loc, 'ab');
            $this->missing = \array_flip(\explode("\n", \file_get_contents($loc)));
        }
    }

    public static function boot(): void
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
    public static function exists($lang): bool
    {
        return \file_exists(APP_ROOT.'/inc/lang/'.$lang.'.php');
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return mixed|string|null
     */
    public function get($key, $default = null): mixed
    {
        if (!isset($this->lang[$key])) {
            $this->isMissing($key);
        }

        $lang = isset($this->lang[$key]) && !empty($this->lang[$key]) ? $this->lang[$key] : (isset(static::$fallback[$key]) && !empty(static::$fallback[$key]) ? static::$fallback[$key] : $key);

        if ($lang === $key && !\is_numeric($default)) {
            if (!\is_null($default) && !\is_array($default)) {
                $lang = $default;
            }

            return $lang;
        }

        if (\is_array($lang) && \is_numeric($default)) {
            $lang = \sprintf((int) $default === 1 ? $lang[0] : $lang[1], $default);
        } else {
            $lang = \is_array($default) ? \vsprintf($lang, $default) : \sprintf($lang, $default);
        }

        return $lang;
    }

    private function isMissing($key)
    {
        if (!isset($this->missing[$key])) {
            \fwrite($this->missingFile, $key."\n");
        }

        $this->missing[$key] = '';
    }

    public function closeFile()
    {
        if ($this->missingFile) {
            \fclose($this->missingFile);
        }
    }
}
