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

use K_Load\Models\User;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Markup;
use Twig\TwigFunction;
use function array_replace_recursive;
use function count;
use function end;
use function explode;
use function file_exists;
use function filemtime;
use function floor;
use function glob;
use function ini_get;
use function json_encode;
use function lang;
use function ltrim;
use function sprintf;
use function str_replace;
use function usort;
use const APP_LANGUAGE;
use const DEBUG;
use const DEMO_MODE;
use const DIRECTORY_SEPARATOR;

class Template
{
    public static $theme = 'default';

    private static $data = [];

    /**
     * @var \Twig_Environment
     */
    private static $twig;

    private static $twig_loader;

    private static $twig_env_params;

    public static function theme($theme = null)
    {
        if (isset($theme) && file_exists(APP_ROOT.'/themes/'.$theme)) {
            self::$theme = $theme;
        }

        return self::$theme;
    }

    public static function isDashboardTheme($name)
    {
        return file_exists(APP_ROOT.'/themes/'.$name.'/pages/controllers');
    }

    public static function isLoadingTheme($name)
    {
        return file_exists(APP_ROOT.'/themes/'.$name.'/pages/loading.twig');
    }

    public static function dashboardThemes()
    {
        $list = [];
        $themes = glob(APP_ROOT.sprintf('%sthemes%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
        foreach ($themes as $location) {
            $tmp = explode(DIRECTORY_SEPARATOR, $location);
            $name = end($tmp);
            $location .= '/pages';
            if (file_exists($location.'/dashboard') && file_exists($location.'/admin')) {
                $list[] = $name;
            }
        }

        return $list;
    }

    public static function render($template, array $data = [], $dontBuild = false)
    {
        if (!isset($data['alert']) && isset($_SESSION['flash']['alert'])) {
            if (isset($_SESSION['flash']['alert'])) {
                $data['alert'] = $_SESSION['flash']['alert'];
                unset($_SESSION['flash']['alert']);
            }
        }

        if (empty(self::$twig)) {
            self::init();
        }

        if (!$dontBuild) {
            self::buildData($data);
            $data = self::$data;
        }

        $data['flash'] = [];

        if (isset($_SESSION['flash'])) {
            foreach ($_SESSION['flash'] as $flashKey => $flashValue) {
                if ($flashKey === 'alerts') {
                    $flashValue = new Markup(json_encode($flashValue), 'utf-8');
                }

                $data['flash'][$flashKey] = $flashValue;

                if (!isset($_SESSION['reflash'])) {
                    unset($_SESSION['flash'][$flashKey]);
                }
            }
        }

        $data['query_log'] = db()->getQueryLog();

        return self::$twig->load($template)->render($data);
    }

    public static function init()
    {
        global $steamLogin;

        $theme_loc = APP_ROOT.'/themes/'.self::$theme.'/pages';

        self::$twig_loader = new FilesystemLoader($theme_loc);

        self::$twig_loader->addPath($theme_loc.'/partials', 'partials');
        self::$twig_loader->addPath($theme_loc.'/controllers', 'controllers');

        self::$twig_env_params = [
            'auto_reload' => true,
            'debug'       => DEBUG,
            'cache'       => (ENABLE_CACHE ? APP_ROOT.'/data/cache/templates' : false),
        ];
        self::$twig = new Environment(self::$twig_loader, self::$twig_env_params);

        if (DEBUG) {
            self::$twig->addExtension(new DebugExtension());
        }

        // csrf
        $function = new TwigFunction('csrf', function () {
            return new Markup('<input type="hidden" name="csrf" value="'.User::getCSRF($_SESSION['steamid'] ?? null).'">', 'utf8');
        });
        self::$twig->addFunction($function);

        $function = new TwigFunction('csrf_token', function () {
            return User::getCSRF($_SESSION['steamid'] ?? null);
        });
        self::$twig->addFunction($function);

        // theme assets
        $function = new TwigFunction('theme_asset', function ($file) {
            return APP_PATH.'/themes/'.self::$theme.'/assets/'.ltrim($file, '/');
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

        // can
        $function = new TwigFunction('can', function ($perm) {
            return User::can($perm);
        });
        self::$twig->addFunction($function);

        // canOr
        $function = new TwigFunction('canOr', function (...$perms) {
            return User::canOr(...$perms);
        });
        self::$twig->addFunction($function);

        // canAnd
        $function = new TwigFunction('canAnd', function (...$perms) {
            return User::canAnd(...$perms);
        });
        self::$twig->addFunction($function);

        unset($function);

        $site_urls = [
            'host'    => APP_HOST,
            'path'    => APP_PATH,
            'url'     => APP_URL,
            'current' => APP_URL_CURRENT,
        ];

        self::$twig->addGlobal('site', $site_urls);

        $app = [
            'debug'               => DEBUG,
            'lang'                => APP_LANGUAGE,
            'demo_mode'           => DEMO_MODE,
            'post_max_size'       => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'max_file_uploads'    => ini_get('max_file_uploads'),
        ];

        $app['true_max_file_uploads'] = floor(Util::convertIniStringToBytes($app['post_max_size']) / Util::convertIniStringToBytes($app['upload_max_filesize']));

        self::$twig->addGlobal('app', $app);

        self::$data = [
            'assets'       => APP_PATH.'/assets',
            'assets_theme' => APP_PATH.'/themes/'.self::$theme.'/assets',
            'login_url'    => $steamLogin->getLoginURL(),
            'site_json'    => new Markup(json_encode($site_urls), 'utf-8'),
            'cache_buster' => Util::hash(3),
        ];
    }

    public static function buildData(array $data = [])
    {
        if (isset($_SESSION['steamid']) && !isset($_GET['steamid']) && (APP_URL.'/') != APP_URL_CURRENT) {
            self::$data['themes'] = self::loadingThemes(User::isSuper($_SESSION['steamid']));
            self::$data['user'] = $_SESSION;
            self::$data['user']['admin'] = User::isSuper($_SESSION['steamid']) ? 1 : $_SESSION['admin'];
            self::$data['user']['super'] = User::isSuper($_SESSION['steamid']);
        }

        self::$data = array_replace_recursive(self::$data, $data);
    }

    public static function loadingThemes($all = false)
    {
        global $config;

        $list = [];
        $themes = glob(APP_ROOT.sprintf('%sthemes%s*', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), GLOB_ONLYDIR);
        foreach ($themes as $location) {
            $tmp = explode(DIRECTORY_SEPARATOR, $location);
            $name = end($tmp);
            if (file_exists($location.'/pages/loading.twig')) {
                if ($all || in_array($name, $config['loading_themes'])) {
                    $previews = glob($location.DIRECTORY_SEPARATOR.'*.{jpg,png}', GLOB_BRACE);

                    if (count($previews) > 0) {
                        usort($previews, function ($a, $b) {
                            return filemtime($a) - filemtime($b);
                        });

                        $preview = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(APP_ROOT, APP_PATH, $previews[0]));
                    } else {
                        $preview = null;
                    }

                    $list[] = [
                        'name'    => $name,
                        'preview' => $preview ?? APP_PATH.'/assets/img/theme.jpg',
                    ];
                }
            }
        }

        return $list;
    }

    public static function renderReturn($template, array $data = [], $dontBuild = false)
    {
        if (!$dontBuild) {
            self::buildData($data);
            $data = self::$data;
        }

        return self::$twig->render($template, $data);
    }

    public static function getData()
    {
        self::buildData();

        return self::$data;
    }

    public static function setData(array $data)
    {
        self::$data = $data;
    }
}
