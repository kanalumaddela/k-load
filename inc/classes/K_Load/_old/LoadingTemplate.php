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

namespace K_Load\_old;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Markup;
use Twig\TwigFunction;

class LoadingTemplate
{
    protected static $data = [];

    /**
     * @var \Twig\Environment
     */
    protected static $twig;

    protected static $twig_loader;

    protected static $twig_env_params = [];

    public static function init($theme = 'default')
    {
        if (!Template::isLoadingTheme($theme)) {
            $theme = 'default';
        }

        $theme_loc = APP_ROOT.'/themes/'.$theme.'/pages';

        self::$twig_loader = new FilesystemLoader($theme_loc);
        self::$twig_loader->addPath(APP_ROOT.'/themes/default/pages/loading', 'loading');

        self::$twig_env_params = [
            'auto_reload' => true,
            'cache'       => (ENABLE_CACHE ? APP_ROOT.'/data/cache/templates' : false),
        ];

        self::$twig = new Environment(self::$twig_loader, self::$twig_env_params);

        // theme assets
        $function = new TwigFunction('theme_asset', function ($file) use ($theme) {
            return APP_PATH.'/themes/'.$theme.'/assets/'.ltrim($file, '/');
        });
        self::$twig->addFunction($function);

        // assets
        $function = new TwigFunction('asset', function ($file) {
            return APP_PATH.'/assets/'.ltrim($file, '/');
        });
        self::$twig->addFunction($function);

        // lang
        $function = new TwigFunction('lang', function ($key, $default = null, $raw = false) {
            return $raw ? new Markup(lang($key, $default), 'utf-8') : lang($key, $default);
        });
        self::$twig->addFunction($function);

        $site_urls = [
            'host'    => APP_HOST,
            'path'    => APP_PATH,
            'url'     => APP_URL,
            'current' => APP_URL_CURRENT,
        ];
        self::$twig->addGlobal('site', $site_urls);

        self::$data = [
            'assets'       => APP_PATH.'/assets',
            'assets_theme' => APP_PATH.'/themes/'.$theme.'/assets',
            'site_json'    => new Markup(json_encode($site_urls), 'utf-8'),
            'cache_buster' => Util::hash(3),
        ];
    }

    public static function render($template, array $data = [])
    {
        self::$data = array_replace_recursive(self::$data, $data);

        return self::$twig->load($template)->render(self::$data);
    }
}
