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

use Exception;
use InvalidArgumentException;
use KLoad\App;
use KLoad\Facades\Config;
use KLoad\Facades\Lang;
use KLoad\Facades\Session;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\Markup;
use Twig\TwigFunction;
use function array_merge;
use function array_slice;
use function bin2hex;
use function file_exists;
use function ini_get;
use function is_array;
use function is_int;
use function json_encode;
use function random_bytes;
use function scandir;
use function str_replace;
use function strpos;
use function substr;
use function substr_count;
use function vsprintf;
use const KLoad\APP_CURRENT_ROUTE;
use const KLoad\APP_CURRENT_URL;
use const KLoad\APP_HOST;
use const KLoad\APP_PATH;
use const KLoad\APP_ROOT;
use const KLoad\APP_ROUTE_URL;
use const KLoad\APP_URL;
use const KLoad\DEBUG;
use const KLoad\ENABLE_CACHE;

class View
{
    protected static string $theme;

    /**
     * @var Environment
     */
    protected static Environment $twig;

    /**
     * @var LoaderInterface
     */
    protected static LoaderInterface $twigLoader;

    /**
     * @var array
     */
    protected static array $globalData = [
        'app' => [
            'host'          => APP_HOST,
            'path'          => APP_PATH,
            'url'           => APP_URL,
            'current'       => APP_CURRENT_URL,
            'route'         => APP_ROUTE_URL,
            'current_route' => APP_CURRENT_ROUTE,
            'debug'         => DEBUG,
        ],
        'assets' => APP_PATH.'/assets',
    ];

    public static function init()
    {
        // todo
    }

    public static function getThemes(): array
    {
        $themePath = APP_ROOT.'/themes/';
        $list = array_slice(scandir(APP_ROOT.'/themes'), 2);
        $themes = [];

        foreach ($list as $theme) {
            if ($theme === '.template') {
                continue;
            }
            if (file_exists($themePath.$theme.'/pages/controllers')) {
                $themes[] = $theme;
            }
        }

        return $themes;
    }

    public static function themeExists(string $theme): bool
    {
        return file_exists(APP_ROOT.'/themes/'.$theme);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public static function render($template, array $data = [], $includeGlobalData = true): string
    {
        static::$twigLoader = new FilesystemLoader();
        static::setDefaultPaths();

//        dd(DEBUG);

        static::$twig = new Environment(static::$twigLoader, [
            'debug' => DEBUG,
            'cache' => ENABLE_CACHE ? APP_ROOT.'/data/templates' : false,
        ]);

        if (DEBUG) {
            static::$twig->addExtension(new DebugExtension());
        }

        static::addFunctions();

        $data = array_merge($data, static::buildData());

        return static::$twig->render(strpos($template, '.twig') !== false ? $template : $template.'.twig', $data);
    }

    /**
     * @throws LoaderError
     */
    public static function setDefaultPaths(): void
    {
        $theme = static::getTheme();

        $requiredFiles = [
            APP_ROOT.'/themes/'.$theme.'/pages',
            'controllers' => APP_ROOT.'/themes/'.$theme.'/pages/controllers',
        ];

        $optionalFiles = [
            'partials' => APP_ROOT.'/themes/'.$theme.'/pages/partials',
        ];

        if (!file_exists(APP_ROOT.'/themes/'.$theme.'/pages')) {
            throw new InvalidArgumentException('`'.$theme.'` does not exist in themes/');
        }

        foreach ($requiredFiles as $key => $requiredFile) {
            static::$twigLoader->addPath($requiredFile, is_int($key) ? FilesystemLoader::MAIN_NAMESPACE : $key);
        }

        foreach ($optionalFiles as $key => $optionalFile) {
            if (file_exists($optionalFile)) {
                static::$twigLoader->addPath($optionalFile, is_int($key) ? FilesystemLoader::MAIN_NAMESPACE : $key);
            }
        }
    }

    public static function getTheme(): string
    {
        if (empty(static::$theme)) {
            static::setTheme(Config::get('dashboard_theme', 'default'));
        }

        return static::$theme;
    }

    public static function setTheme(string $theme): void
    {
        static::$theme = $theme;
    }

    public static function addFunctions(): void
    {
        self::$twig->addFunction(new TwigFunction('lang', [Lang::class, 'get']));
        self::$twig->addFunction(new TwigFunction('can', function (...$perms) {
            if (empty($user = Session::get('user', []))) {
                return false;
            }

            $userPerms = $userPerms['perms'] ?? [];

            if (isset($user['super']) && $user['super']) {
                return true;
            }

            foreach ($perms as $perm) {
                if (!isset($userPerms[$perm])) {
                    return false;
                }
            }

            return true;
        }));
        self::$twig->addFunction(new TwigFunction('asset', function ($file) {
            return APP_URL.'/assets/'.$file;
        }));
        self::$twig->addFunction(new TwigFunction('theme_asset', function ($file) {
            return APP_URL.'/themes/'.static::getTheme().'/assets/'.$file;
        }));
        self::$twig->addFunction(new TwigFunction('route', function ($route, ...$parameters) {
            if (isset($parameters[0]) && is_array($parameters[0])) {
                $parameters = $parameters[0];
            }

            if (substr_count($route, '?') > 0) {
                $route = str_replace('?', '%s', $route);
            }

            $route = vsprintf($route, $parameters);

            return APP_ROUTE_URL.'/'.$route;
        }));
        self::$twig->addFunction(new TwigFunction('isActiveRoute', function ($route, $activeClass = 'is-active') {
            return APP_CURRENT_ROUTE === $route || substr(APP_CURRENT_ROUTE, 1) === $route ? $activeClass : '';
        }));
        self::$twig->addFunction(new TwigFunction('csrf', function ($route = null) {
            if (empty($route)) {
                $route = APP_CURRENT_ROUTE;
            }

            $csrf = Session::generateCsrf($route);

            return new Markup('<input type="hidden" value="'.$csrf.'" name="_csrf" />', 'utf-8');
        }));
    }

    /**
     * @throws Exception
     */
    public static function buildData(): array
    {
        static::setupBaseData();

        return [];
    }

    /**
     * @throws Exception
     */
    public static function setupBaseData(): void
    {
        self::$globalData['app'] = array_merge(self::$globalData['app'], [
            'lang'       => Lang::getCurrentLang(),
            'demo_mode'  => Config::get('demo_mode', false),
            'version'    => App::$version,
            'version_id' => App::$versionId,
        ]);

        self::$globalData = array_merge(self::$globalData, [
            'assets_theme'        => APP_PATH.'/themes/'.static::getTheme().'/assets',
            'cache_buster'        => bin2hex(random_bytes(4)),
            'post_max_size'       => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'max_file_uploads'    => ini_get('max_file_uploads'),
        ]);

        self::$globalData['site_json'] = new Markup(json_encode(self::$globalData['app'], JSON_THROW_ON_ERROR), 'utf-8');

        if (App::has('steamLogin')) {
            static::$twig->addGlobal('steam_login_url', App::get('steamLogin')->getLoginURL());
        }

        foreach (self::$globalData as $name => $data) {
            self::$twig->addGlobal($name, $data);
        }

        if (isset($_SESSION['kload']) && Session::has('user')) {
            self::$twig->addGlobal('user', Session::user());

            $csrf = Session::get('csrf.'.APP_CURRENT_ROUTE.'.token');

            self::$twig->addGlobal('csrf_token', $csrf);
            self::$twig->addGlobal('csrf', new Markup('<input type="hidden" value="'.$csrf.'" name="_csrf" />', 'utf-8'));

            $flash = Session::flushFlash();
            self::$twig->addGlobal('flash', $flash);
            self::$twig->addGlobal('old', $flash['old'] ?? []);
        }
    }

    public static function addGlobal($name, $data): void
    {
        self::$globalData[$name] = $data;

        if (!empty(self::$twig)) {
            self::$twig->addGlobal($name, $data);
        }
    }
}
