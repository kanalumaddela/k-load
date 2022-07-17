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

namespace KLoad\View;

use Illuminate\Support\Str;
use KLoad\Facades\Config;
use function array_slice;
use function file_exists;
use function scandir;
use const KLoad\APP_ROOT;
use const KLoad\APP_URL;

class LoadingView extends View
{
    public static function getThemes($withPreviews = false): array
    {
        $themePath = APP_ROOT.'/themes/';
        $list = array_slice(scandir(APP_ROOT.'/themes'), 2);
        $themes = [];

        foreach ($list as $theme) {
            if ($theme === '.template') {
                continue;
            }
            if (file_exists($themePath.$theme.'/pages/loading.twig')) {
                if ($withPreviews) {
                    $tmp = array_slice(scandir(APP_ROOT.'/themes/'.$theme), 2);

                    foreach ($tmp as $file) {
                        if (Str::endsWith($file, ['.jpg', '.jpeg', '.png'])) {
                            $themes[$theme] = APP_URL.'/themes/'.$theme.'/'.$file;

                            break;
                        }
                    }

                    $themes[$theme] = $themes[$theme] ?? null;
                    unset($tmp);
                } else {
                    $themes[] = $theme;
                }
            }
        }

        return $themes;
    }

    public static function themeExists(string $theme): bool
    {
        return parent::themeExists($theme) && file_exists(APP_ROOT.'/themes/'.$theme.'/pages/loading.twig');
    }

    public static function setDefaultPaths()
    {
        $theme = static::getTheme();

        static::$twigLoader->addPath(APP_ROOT . '/themes/' . $theme . '/pages');
        static::$twigLoader->addPath(APP_ROOT . '/themes/base', 'base');
    }

    public static function getTheme(): string
    {
        if (empty(static::$theme)) {
            static::setTheme(Config::get('loading_theme', 'default'));
        }

        return static::$theme;
    }

    public static function getThemeConfig(): array
    {
        return file_exists(APP_ROOT . '/themes/' . static::getTheme() . '/config.php') ? include APP_ROOT . '/themes/' . static::getTheme() . '/config.php' : [];
    }
}
// 008f68
